<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| SQLite connection and queries
|
*/


class ParksSQLite
{


	/**
	 * API
	 */
	public ParksAPI $api;


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
	protected string $last_error = '';



	/**
	 * Constructor
	 */
	public function __construct(ParksAPI $api)
	{

		// Api instance
		$this->api = $api;

		// Resolve database file path (relative paths are relative to parks_api/)
		$path = ! empty($api->config['db_path']) ? $api->config['db_path'] : 'data/park-offers.sqlite';
		if (substr($path, 0, 1) !== '/') {
			$path = $api->config['absolute_path'] . '/' . $path;
		}
		$this->path = $path;

		$this->connect();

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
		$this->_register_functions();

		// Create schema on first run
		$has_schema = $this->connection->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'api'");
		if (($has_schema === false) || (count($has_schema->fetchAll()) === 0)) {
			$this->_create_schema();
		}

		return true;
	}



	/**
	 * Create database schema from SQL file
	 */
	private function _create_schema(): void
	{

		$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
		if (empty($schema)) {
			throw new RuntimeException('The database schema file does not exist.');
		}

		if ($this->connection->exec($schema) === false) {
			throw new RuntimeException('The database schema could not be created: ' . $this->get_last_error());
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
	public function get_last_error(): string
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
	public function query(string $sql): ParksSQLiteResult|bool
	{

		$statement = $this->connection->query($sql);

		if ($statement === false) {
			$this->last_error = $this->get_last_error();
			return false;
		}

		// Return result rows for SELECT-like queries
		if ($statement->columnCount() > 0) {
			return new ParksSQLiteResult($statement->fetchAll(PDO::FETCH_ASSOC));
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
		?array $left_outer_joins = null,
		?string $order_by = null
	): ParksSQLiteResult|bool {

		// Select
		$db_select = "*";
		if ($select && is_array($select)) {
			$db_select = "*";
			foreach ($select as $key => $value) {
				if (!is_numeric($key)) {
					$db_select .= ", " . $key . " AS " . $value;
				} else {
					$db_select .= ", " . $value;
				}
			}
		}

		// Inner joins
		$db_joins = "";
		if ($joins && is_array($joins)) {
			foreach ($joins as $key => $value) {
				$db_joins .= " INNER JOIN `" . $key . "` ON " . $value;
			}
			$db_joins .= " ";
		}

		// Left outer joins
		if ($left_outer_joins && is_array($left_outer_joins)) {
			foreach ($left_outer_joins as $key => $value) {
				$db_joins .= " LEFT OUTER JOIN `" . $key . "` ON " . $value;
			}
		}

		// Where
		$db_where = "";
		if ($where && is_array($where)) {
			$db_where = " WHERE ";
			foreach ($where as $key => $value) {
				$db_where .= $key . " = '" . $this->escape($value) . "' AND ";
			}
			$db_where = substr($db_where, 0, -4);
		}

		$db_limit = "";
		if ($limit && is_numeric($limit)) {
			$db_limit = " LIMIT " . (isset($offset) && is_numeric($offset) ? intval($offset) . ', ' : '') . intval($limit);
		}

		// Order by
		if (! empty($order_by)) {
			$order_by = " ORDER BY " . $order_by;
		}

		return $this->query("SELECT " . $db_select . " FROM `" . $table . "`" . $db_joins . $db_where . $order_by . $db_limit . ";");
	}



	/**
	 * Insert new row into database table
	 */
	public function insert(string $table, array $fields): bool
	{

		$db_fields = '';
		$db_values = '';
		foreach ($fields as $key => $value) {

			if (! empty($value)) {
				$value = str_replace("\'", "'", $value); // Normalize legacy escaped quotes
				$value = $this->escape($value);
			}

			$db_fields .= "`" . $key . "` , ";

			if ($value === null) {
				$db_values .= "NULL, ";
			} else {
				$db_values .= "'" . $value . "' , ";
			}
		}
		$db_fields = substr($db_fields, 0, -2);
		$db_values = substr($db_values, 0, -2);

		$status = (bool) $this->query("INSERT INTO `" . $table . "` (" . $db_fields . ") VALUES (" . $db_values . ");");

		if (! $status) {
			$this->api->logger->error("INSERT INTO `" . $table . "` (" . $db_fields . ") VALUES (" . $db_values . ");");
		}

		return $status;
	}



	/**
	 * Update existing rows in database table
	 */
	public function update(string $table, array $fields, ?array $where = null): bool
	{

		$db_fields = '';
		foreach ($fields as $key  => $value) {

			if (! empty($value)) {
				$value = str_replace("\'", "'", $value); // Normalize legacy escaped quotes
				$value = $this->escape($value);
			}

			if ($value === null) {
				$db_fields .= "`" . $key . "` = null, ";
			} else {
				$db_fields .= "`" . $key . "` = '" . $value . "', ";
			}
		}
		$db_fields = substr($db_fields, 0, -2);

		$db_where = '';
		if ($where && is_array($where)) {
			$db_where = " WHERE ";
			foreach ($where as $key => $value) {
				$db_where .= "`" . $key . "` = '" . $this->escape($value) . "' AND ";
			}
			$db_where = substr($db_where, 0, -4);
		}

		$status = (bool) $this->query("UPDATE `" . $table . "` SET " . $db_fields . $db_where . ";");

		if (! $status) {
			$this->api->logger->error("UPDATE `" . $table . "` SET " . $db_fields . $db_where . ";");
		}

		return $status;
	}



	/**
	 * Delete rows from database table
	 */
	public function delete(string $table, array $where = []): bool
	{

		$db_where = '';
		if (! empty($where) && is_array($where)) {
			$db_where = " WHERE ";
			foreach ($where as $key  => $value) {
				$db_where .= "`" . $key . "` = '" . $this->escape($value) . "' AND ";
			}
			$db_where = substr($db_where, 0, -4);
		}

		$status = (bool) $this->query("DELETE FROM `" . $table . "`" . $db_where . ";");

		if (! $status) {
			$this->api->logger->error("DELETE FROM `" . $table . "`" . $db_where . ";");
		}

		return $status;
	}



	/**
	 * Register a custom SQL function
	 * (PDO::sqliteCreateFunction is deprecated since PHP 8.5)
	 */
	private function _create_function(string $name, callable $callback, int $num_args): void
	{

		if ($this->connection instanceof \Pdo\Sqlite) {
			$this->connection->createFunction($name, $callback, $num_args);
		} else {
			$this->connection->sqliteCreateFunction($name, $callback, $num_args);
		}

	}



	/**
	 * Register MySQL compatible SQL functions
	 */
	private function _register_functions(): void
	{

		// NOW()
		$this->_create_function('NOW', function () {
			return date('Y-m-d H:i:s');
		}, 0);

		// CURDATE()
		$this->_create_function('CURDATE', function () {
			return date('Y-m-d');
		}, 0);

		// RAND()
		$this->_create_function('RAND', function () {
			return mt_rand() / mt_getrandmax();
		}, 0);

		// DATE_FORMAT(date, format) with MySQL format specifiers
		$this->_create_function('DATE_FORMAT', function ($value, $format) {

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
		$this->_create_function('DATEDIFF', function ($date1, $date2) {

			$time1 = ($date1 !== null) ? strtotime(substr((string) $date1, 0, 10)) : false;
			$time2 = ($date2 !== null) ? strtotime(substr((string) $date2, 0, 10)) : false;

			if (($time1 === false) || ($time2 === false)) {
				return null;
			}

			return (int) round(($time1 - $time2) / 86400);
		}, 2);

		// CONCAT(...)
		$this->_create_function('CONCAT', function (...$values) {
			return implode('', array_map(fn ($value) => (string) $value, $values));
		}, -1);

		// CONCAT_WS(separator, ...) skips NULL values like MySQL
		$this->_create_function('CONCAT_WS', function ($separator, ...$values) {
			return implode($separator, array_filter($values, fn ($value) => $value !== null));
		}, -1);

	}



}
