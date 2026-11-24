<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/

namespace SwissParks\Sdk;

use PDO;
use PDOException;
use RuntimeException;


class SQLite
{


	/**
	 * SDK instance
	 */
	public SwissParksSdk $sdk;


	/**
	 * PDO connection
	 */
	private ?PDO $connection = null;


	/**
	 * Path to the SQLite database file
	 */
	protected string $path;


	/**
	 * Last error message
	 */
	protected string $lastError = '';


	/**
	 * Whether offer_fts (FTS5) is available
	 */
	private bool $offerFtsAvailable = false;



	/**
	 * Constructor
	 */
	public function __construct(SwissParksSdk $sdk)
	{

		$this->sdk = $sdk;

		// Resolve database file path (relative paths are relative to swiss-parks-sdk/)
		$path = ! empty($sdk->config['db_path']) ? $sdk->config['db_path'] : 'writable/data/park-offers.sqlite';
		if (substr($path, 0, 1) !== '/') {
			$path = $sdk->config['absolute_path'] . '/' . $path;
		}
		$this->path = $path;

		$this->connect();

	}



	/**
	 * Whether full-text search table is usable
	 */
	public function isOfferFtsAvailable(): bool
	{

		return $this->offerFtsAvailable;
	}



	/**
	 * Connect to database and create schema if needed
	 */
	private function connect(): bool
	{

		// Make sure the data directory exists and is protected from web access
		$directory = dirname($this->path);
		if (! is_dir($directory)) {
			mkdir($directory, 0775, true);
		}
		if (! file_exists($directory . '/.htaccess')) {
			file_put_contents($directory . '/.htaccess', "Require all denied\n");
		}

		// Open connection (PDO::connect returns the Pdo\Sqlite subclass on PHP >= 8.4)
		try {
			$dsn = 'sqlite:' . $this->path;
			$this->connection = method_exists(PDO::class, 'connect') ? PDO::connect($dsn) : new PDO($dsn);
		} catch (PDOException $e) {
			throw new RuntimeException('Connect Error: ' . $e->getMessage(), 0, $e);
		}

		// Return false instead of throwing exceptions (like mysqli did)
		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

		// Pragmas: enforce foreign keys, allow concurrent reads during imports
		$this->connection->exec('PRAGMA foreign_keys = ON;');
		$this->connection->exec('PRAGMA journal_mode = WAL;');
		$this->connection->exec('PRAGMA synchronous = NORMAL;');
		$this->connection->exec('PRAGMA busy_timeout = 5000;');

		// Register MySQL compatible SQL functions
		$this->registerFunctions();

		// Legacy v22 DBs used table name api; rename in place so createSchema is not triggered
		$this->ensureMetaTable();

		// Create schema on first run (offer marks an existing DB even if meta is missing)
		$hasSchema = $this->connection->query(
			"SELECT name FROM sqlite_master WHERE type = 'table' AND name IN ('meta', 'offer')"
		);
		if (($hasSchema === false) || (count($hasSchema->fetchAll()) === 0)) {
			$this->createSchema();
			$this->ensureOfferFts(true);
		} else {
			$this->ensurePerformanceIndexes();
			$this->ensureOfferFts(false);
		}

		return true;
	}



	/**
	 * Ensure meta table exists on legacy or partial databases
	 */
	private function ensureMetaTable(): void
	{

		$tables = $this->connection->query(
			"SELECT name FROM sqlite_master WHERE type = 'table' AND name IN ('api', 'meta', 'offer')"
		);
		if ($tables === false) {
			return;
		}

		$names = [];
		foreach ($tables->fetchAll(PDO::FETCH_COLUMN) as $name) {
			$names[(string) $name] = true;
		}

		if (isset($names['meta'])) {
			return;
		}

		// Legacy v22 marker table
		if (isset($names['api'])) {
			$this->connection->exec('ALTER TABLE `api` RENAME TO `meta`');
			return;
		}

		// Partial DB: offers exist but meta row table is missing
		if (isset($names['offer'])) {
			$this->connection->exec(
				'CREATE TABLE `meta` (
					`initialized` INTEGER DEFAULT 0,
					`version` TEXT DEFAULT \'1.0\',
					`last_import` INTEGER DEFAULT NULL
				)'
			);
		}
	}



	/**
	 * Add missing read-path indexes on existing databases (no rebuild required)
	 */
	private function ensurePerformanceIndexes(): void
	{

		$indexes = [
			'idx_offer_park_id' => 'CREATE INDEX IF NOT EXISTS `idx_offer_park_id` ON `offer` (`park_id`)',
			'idx_offer_date_date_from' => 'CREATE INDEX IF NOT EXISTS `idx_offer_date_date_from` ON `offer_date` (`date_from`)',
			'idx_field_of_activity_link_field_of_activity_id' => 'CREATE INDEX IF NOT EXISTS `idx_field_of_activity_link_field_of_activity_id` ON `field_of_activity_link` (`field_of_activity_id`)',
			'idx_offer_municipality_link_municipality_id' => 'CREATE INDEX IF NOT EXISTS `idx_offer_municipality_link_municipality_id` ON `offer_municipality_link` (`municipality_id`)',
		];

		foreach ($indexes as $sql) {
			$this->connection->exec($sql);
		}
	}



	/**
	 * Ensure offer_fts exists; optionally rebuild when empty
	 */
	private function ensureOfferFts(bool $freshSchema): void
	{

		$createSql = "
			CREATE VIRTUAL TABLE IF NOT EXISTS `offer_fts` USING fts5(
				`offer_id` UNINDEXED,
				`language` UNINDEXED,
				`body`,
				tokenize = 'unicode61 remove_diacritics 2'
			)
		";

		// Older SQLite builds without FTS5 fall back to LIKE search
		if ($this->connection->exec($createSql) === false) {
			$this->offerFtsAvailable = false;
			return;
		}

		$this->offerFtsAvailable = true;

		// Fresh schema is filled by the upcoming import; nothing to backfill yet
		if ($freshSchema) {
			return;
		}

		// Existing DB with offers but empty FTS (e.g. upgrade): rebuild once
		$ftsCount = $this->connection->query('SELECT COUNT(*) AS total FROM offer_fts');
		$offerCount = $this->connection->query('SELECT COUNT(*) AS total FROM offer');
		if ($ftsCount === false || $offerCount === false) {
			return;
		}

		$ftsTotal = (int) ($ftsCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
		$offerTotal = (int) ($offerCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
		if (($ftsTotal === 0) && ($offerTotal > 0)) {
			$this->rebuildOfferFts();
		}
	}



	/**
	 * Rebuild offer_fts from offer + offer_i18n (+ keywords)
	 */
	public function rebuildOfferFts(?array $offerIds = null): bool
	{

		if (! $this->offerFtsAvailable) {
			return false;
		}

		// Partial rebuild for changed offers; full wipe when no ID list is given
		$where = '';
		if ($offerIds !== null) {
			$ids = array_values(array_unique(array_filter(array_map('intval', $offerIds))));
			if (empty($ids)) {
				return true;
			}
			$idsSql = implode(',', $ids);
			if ($this->connection->exec("DELETE FROM offer_fts WHERE offer_id IN (" . $idsSql . ")") === false) {
				return false;
			}
			$where = " WHERE offer.offer_id IN (" . $idsSql . ")";
		} else {
			if ($this->connection->exec('DELETE FROM offer_fts') === false) {
				return false;
			}
		}

		// One searchable body per offer/language: keywords plus the main i18n text fields
		$sql = "
			INSERT INTO offer_fts (offer_id, language, body)
			SELECT
				offer.offer_id,
				offer_i18n.language,
				IFNULL(offer.keywords, '') || ' ' ||
				IFNULL(offer_i18n.title, '') || ' ' ||
				IFNULL(offer_i18n.abstract, '') || ' ' ||
				IFNULL(offer_i18n.description_medium, '') || ' ' ||
				IFNULL(offer_i18n.description_long, '') || ' ' ||
				IFNULL(offer_i18n.details, '') || ' ' ||
				IFNULL(offer_i18n.price, '') || ' ' ||
				IFNULL(offer_i18n.location_details, '') || ' ' ||
				IFNULL(offer_i18n.opening_hours, '') || ' ' ||
				IFNULL(offer_i18n.benefits, '') || ' ' ||
				IFNULL(offer_i18n.requirements, '') || ' ' ||
				IFNULL(offer_i18n.additional_informations, '') || ' ' ||
				IFNULL(offer_i18n.catering_informations, '') || ' ' ||
				IFNULL(offer_i18n.material_rent, '') || ' ' ||
				IFNULL(offer_i18n.safety_instructions, '') || ' ' ||
				IFNULL(offer_i18n.signalization, '') || ' ' ||
				IFNULL(offer_i18n.other_infrastructure, '') || ' ' ||
				IFNULL(offer_i18n.project_initial_situation, '') || ' ' ||
				IFNULL(offer_i18n.project_goal, '') || ' ' ||
				IFNULL(offer_i18n.project_further_information, '') || ' ' ||
				IFNULL(offer_i18n.project_results, '') || ' ' ||
				IFNULL(offer_i18n.project_partner, '')
			FROM offer
			INNER JOIN offer_i18n ON offer_i18n.offer_id = offer.offer_id
			" . $where . "
		";

		return $this->connection->exec($sql) !== false;
	}



	/**
	 * Create database schema from SQL file
	 */
	private function createSchema(): void
	{

		$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
		if (empty($schema)) {
			throw new RuntimeException('The database schema file does not exist.');
		}

		if ($this->connection->exec($schema) === false) {
			throw new RuntimeException(
				'The database schema could not be created: ' . $this->getLastError()
				. ' If the SQLite file already contains tables, run: bash bin/migrate.sh'
			);
		}

	}



	/**
	 * Recreate database from scratch (drop file and rebuild schema)
	 */
	public function recreate(): void
	{

		// Close connection
		$this->connection = null;

		// Remove database files
		foreach ([$this->path, $this->path . '-wal', $this->path . '-shm'] as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}

		// Reconnect and rebuild schema
		$this->connect();

	}



	/**
	 * Begin transaction (used to speed up imports)
	 */
	public function begin(): void
	{

		if (! $this->connection->inTransaction()) {
			$this->connection->beginTransaction();
		}

	}



	/**
	 * Commit transaction
	 */
	public function commit(): void
	{

		if ($this->connection->inTransaction()) {
			$this->connection->commit();
		}

	}



	/**
	 * Get last error
	 */
	public function getLastError(): string
	{
		$error = $this->connection->errorInfo();

		return $error[2] ?? '';
	}



	/**
	 * Escape string value for SQL
	 */
	public function escape(mixed $value): string
	{
		return str_replace("'", "''", (string) $value);
	}



	/**
	 * Query database
	 */
	public function query(string $sql): SQLiteResult|bool
	{

		$statement = $this->connection->query($sql);

		if ($statement === false) {
			$this->lastError = $this->getLastError();
			return false;
		}

		// Return result rows for SELECT-like queries
		if ($statement->columnCount() > 0) {
			return new SQLiteResult($statement->fetchAll(PDO::FETCH_ASSOC));
		}

		return true;
	}



	/**
	 * Get rows from table
	 */
	public function get(
		string $table,
		?array $where = null,
		?array $joins = null,
		?array $select = null,
		?string $limit = null,
		?string $offset = null,
		?array $leftOuterJoins = null,
		?string $orderBy = null
	): SQLiteResult|bool {

		// Select
		$dbSelect = "*";
		if ($select && is_array($select)) {
			$dbSelect = "*";
			foreach ($select as $key => $value) {
				if (!is_numeric($key)) {
					$dbSelect .= ", " . $key . " AS " . $value;
				} else {
					$dbSelect .= ", " . $value;
				}
			}
		}

		// Inner joins
		$dbJoins = "";
		if ($joins && is_array($joins)) {
			foreach ($joins as $key => $value) {
				$dbJoins .= " INNER JOIN `" . $key . "` ON " . $value;
			}
			$dbJoins .= " ";
		}

		// Left outer joins
		if ($leftOuterJoins && is_array($leftOuterJoins)) {
			foreach ($leftOuterJoins as $key => $value) {
				$dbJoins .= " LEFT OUTER JOIN `" . $key . "` ON " . $value;
			}
		}

		// Where
		$dbWhere = "";
		if ($where && is_array($where)) {
			$dbWhere = " WHERE ";
			foreach ($where as $key => $value) {
				$dbWhere .= $key . " = '" . $this->escape($value) . "' AND ";
			}
			$dbWhere = substr($dbWhere, 0, -4);
		}

		$dbLimit = "";
		if ($limit && is_numeric($limit)) {
			$dbLimit = " LIMIT " . (isset($offset) && is_numeric($offset) ? intval($offset) . ', ' : '') . intval($limit);
		}

		// Order by
		if (! empty($orderBy)) {
			$orderBy = " ORDER BY " . $orderBy;
		}

		return $this->query("SELECT " . $dbSelect . " FROM `" . $table . "`" . $dbJoins . $dbWhere . $orderBy . $dbLimit . ";");
	}



	/**
	 * Insert new row into database table
	 */
	public function insert(string $table, array $fields): bool
	{

		$dbFields = '';
		$dbValues = '';
		foreach ($fields as $key => $value) {

			if (! empty($value)) {
				$value = str_replace("\'", "'", $value); // Normalize legacy escaped quotes
				$value = $this->escape($value);
			}

			$dbFields .= "`" . $key . "` , ";

			if ($value === null) {
				$dbValues .= "NULL, ";
			} else {
				$dbValues .= "'" . $value . "' , ";
			}
		}
		$dbFields = substr($dbFields, 0, -2);
		$dbValues = substr($dbValues, 0, -2);

		$status = (bool) $this->query("INSERT INTO `" . $table . "` (" . $dbFields . ") VALUES (" . $dbValues . ");");

		if (! $status) {
			$this->sdk->logger->error("INSERT INTO `" . $table . "` (" . $dbFields . ") VALUES (" . $dbValues . ");");
		}

		return $status;
	}



	/**
	 * Update existing rows in database table
	 */
	public function update(string $table, array $fields, ?array $where = null): bool
	{

		$dbFields = '';
		foreach ($fields as $key  => $value) {

			if (! empty($value)) {
				$value = str_replace("\'", "'", $value); // Normalize legacy escaped quotes
				$value = $this->escape($value);
			}

			if ($value === null) {
				$dbFields .= "`" . $key . "` = null, ";
			} else {
				$dbFields .= "`" . $key . "` = '" . $value . "', ";
			}
		}
		$dbFields = substr($dbFields, 0, -2);

		$dbWhere = '';
		if ($where && is_array($where)) {
			$dbWhere = " WHERE ";
			foreach ($where as $key => $value) {
				$dbWhere .= "`" . $key . "` = '" . $this->escape($value) . "' AND ";
			}
			$dbWhere = substr($dbWhere, 0, -4);
		}

		$status = (bool) $this->query("UPDATE `" . $table . "` SET " . $dbFields . $dbWhere . ";");

		if (! $status) {
			$this->sdk->logger->error("UPDATE `" . $table . "` SET " . $dbFields . $dbWhere . ";");
		}

		return $status;
	}



	/**
	 * Delete rows from database table
	 */
	public function delete(string $table, array $where = []): bool
	{

		$dbWhere = '';
		if (! empty($where) && is_array($where)) {
			$dbWhere = " WHERE ";
			foreach ($where as $key  => $value) {
				$dbWhere .= "`" . $key . "` = '" . $this->escape($value) . "' AND ";
			}
			$dbWhere = substr($dbWhere, 0, -4);
		}

		$status = (bool) $this->query("DELETE FROM `" . $table . "`" . $dbWhere . ";");

		if (! $status) {
			$this->sdk->logger->error("DELETE FROM `" . $table . "`" . $dbWhere . ";");
		}

		return $status;
	}



	/**
	 * Register a custom SQL function
	 */
	private function createFunction(string $name, callable $callback, int $numArgs): void
	{

		if (method_exists($this->connection, 'createFunction')) {
			$this->connection->createFunction($name, $callback, $numArgs);
			return;
		}

		if (PHP_VERSION_ID >= 80500) {
			throw new RuntimeException('SQLite user-defined functions require PDO::connect() on PHP 8.5+.');
		}

		if (! method_exists($this->connection, 'sqliteCreateFunction')) {
			throw new RuntimeException('SQLite user-defined functions are not supported by this PDO driver.');
		}

		$legacyRegister = 'sqliteCreateFunction';
		$this->connection->{$legacyRegister}($name, $callback, $numArgs);

	}



	/**
	 * Register MySQL compatible SQL functions
	 */
	private function registerFunctions(): void
	{

		// NOW()
		$this->createFunction('NOW', function () {
			return date('Y-m-d H:i:s');
		}, 0);

		// CURDATE()
		$this->createFunction('CURDATE', function () {
			return date('Y-m-d');
		}, 0);

		// RAND()
		$this->createFunction('RAND', function () {
			return mt_rand() / mt_getrandmax();
		}, 0);

		// DATE_FORMAT(date, format) with MySQL format specifiers
		$this->createFunction('DATE_FORMAT', function ($value, $format) {

			if (($value === null) || ($value === '')) {
				return null;
			}

			$timestamp = strtotime((string) $value);
			if ($timestamp === false) {
				return null;
			}

			// Map MySQL format specifiers to PHP date() format
			$map = [
				'%Y' => 'Y',
				'%y' => 'y',
				'%m' => 'm',
				'%c' => 'n',
				'%d' => 'd',
				'%e' => 'j',
				'%H' => 'H',
				'%k' => 'G',
				'%i' => 'i',
				'%s' => 's',
				'%%' => '%',
			];

			return date(strtr($format, $map), $timestamp);
		}, 2);

		// DATEDIFF(date1, date2) returns difference in days
		$this->createFunction('DATEDIFF', function ($date1, $date2) {

			$time1 = ($date1 !== null) ? strtotime(substr((string) $date1, 0, 10)) : false;
			$time2 = ($date2 !== null) ? strtotime(substr((string) $date2, 0, 10)) : false;

			if (($time1 === false) || ($time2 === false)) {
				return null;
			}

			return (int) round(($time1 - $time2) / 86400);
		}, 2);

		// CONCAT(...)
		$this->createFunction('CONCAT', function (...$values) {
			return implode('', array_map(fn ($value) => (string) $value, $values));
		}, -1);

		// CONCAT_WS(separator, ...) skips NULL values like MySQL
		$this->createFunction('CONCAT_WS', function ($separator, ...$values) {
			return implode($separator, array_filter($values, fn ($value) => $value !== null));
		}, -1);

	}


}
