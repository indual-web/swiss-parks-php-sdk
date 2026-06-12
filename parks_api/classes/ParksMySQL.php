<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| MySQL connection and queries
|
*/


class ParksMySQL
{


	/**
	 * API
	 */
	public ParksAPI $api;


	/**
	 * MySQL connection
	 */
	private mysqli $connection;


	/**
	 * MySQL Host
	 */
	protected string $hostname;


	/**
	 * MySQL Username
	 */
	protected string $username;


	/**
	 * MySQL Password
	 */
	protected string $password;


	/**
	 * MySQL Database
	 */
	protected string $database;


	/**
	 * Last error message
	 */
	protected string $last_error = '';



	/**
	 * Constructor
	 *
	 * @param ParksAPI $api
	 */
	public function __construct(ParksAPI $api)
	{

		// Api instance
		$this->api = $api;

		// DB connection
		$this->hostname = $api->config['db_hostname'];
		$this->username = $api->config['db_username'];
		$this->password = $api->config['db_password'];
		$this->database = $api->config['db_database'];

		$this->connect();
		$this->connection->set_charset('utf8');

	}



	/**
	 * Connect to database
	 */
	private function connect(): bool
	{
		$this->connection = new mysqli($this->hostname, $this->username, $this->password, $this->database);

		if ($this->connection->connect_error) {
			die('Connect Error (' . $this->connection->connect_errno . ') ' . $this->connection->connect_error);
		}

		return true;
	}



	/**
	 * Get last mysql error
	 */
	public function get_last_error(): string
	{
		return mysqli_error($this->connection);
	}



	/**
	 * Query database
	 *
	 * @param string $sql
	 */
	public function query(string $sql): mysqli_result|bool
	{
		$result = $this->connection->query($sql);

		if ($result == false) {
			$this->last_error = $this->get_last_error();
			return false;
		}

		return $result;
	}



	/**
	 * Get rows from table
	 *
	 * @param string $table
	 * @param ?array $where
	 * @param ?array $joins
	 * @param ?array $select
	 * @param ?string $limit
	 * @param ?string $offset
	 * @param ?array $left_outer_joins
	 * @param ?string $order_by
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
	): mysqli_result|bool {

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
				$db_joins .= " INNER JOIN `" . $key . "` ON " . $this->connection->real_escape_string($value);
			}
			$db_joins .= " ";
		}

		// Left outer joins
		if ($left_outer_joins && is_array($left_outer_joins)) {
			foreach ($left_outer_joins as $key => $value) {
				$db_joins .= " LEFT OUTER JOIN `" . $key . "` ON " . $this->connection->real_escape_string($value);
			}
		}

		// Where
		$db_where = "";
		if ($where && is_array($where)) {
			$db_where = " WHERE ";
			foreach ($where as $key => $value) {
				$db_where .= $key . " = '" . $this->connection->real_escape_string($value) . "' AND ";
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
	 *
	 * @param string $table
	 * @param array $fields
	 * @param bool $escape
	 */
	public function insert(string $table, array $fields, bool $escape = false): mysqli_result|bool
	{
		$db_fields = '';
		$db_values = '';
		foreach ($fields as $key => $value) {

			if (! empty($value)) {
				if ($escape) {
					$value = str_replace("'", "\'", $value);
				} else {
					$value = str_replace("\'", "'", $value); // Replace all right '
					$value = str_replace("'", "\'", $value); // Do escaping again (for all entries, also for right)
				}
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

		$status = $this->query("INSERT INTO `" . $table . "` (" . $db_fields . ") VALUES (" . $db_values . ");");

		if (! $status) {
			$this->api->logger->error("INSERT INTO `" . $table . "` (" . $db_fields . ") VALUES (" . $db_values . ");");
		}

		return $status;
	}



	/**
	 * Update existing rows in database table
	 *
	 * @param string $table
	 * @param array $fields
	 * @param ?array $where
	 * @param bool $escape
	 */
	public function update(string $table, array $fields, ?array $where = null, bool $escape = false): mysqli_result|bool
	{
		$db_fields = '';
		foreach ($fields as $key  => $value) {

			if (! empty($value)) {
				if ($escape) {
					$value = str_replace("'", "\'", $value);
				} else {
					$value = str_replace("\'", "'", $value); // Replace all right '
					$value = str_replace("'", "\'", $value); // Do escaping again (for all entries, also for right)
				}
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
				$db_where .= "`" . $key . "` = '" . $this->connection->real_escape_string($value) . "' AND ";
			}
			$db_where = substr($db_where, 0, -4);
		}

		$status = $this->query("UPDATE `" . $table . "` SET " . $db_fields . $db_where . ";");

		if (! $status) {
			$this->api->logger->error("UPDATE `" . $table . "` SET " . $db_fields . $db_where . ";");
		}

		return $status;
	}



	/**
	 * Delete rows from database table
	 *
	 * @param string $table
	 * @param array $where
	 * @param bool|int $limit
	 */
	public function delete(string $table, array $where = [], bool|int $limit = false): mysqli_result|bool
	{
		$db_where = '';
		if (! empty($where) && is_array($where)) {
			$db_where = " WHERE ";
			foreach ($where as $key  => $value) {
				$db_where .= "`" . $key . "` = '" . $value . "' AND ";
			}
			$db_where = substr($db_where, 0, -4);
		}

		$db_limit = '';
		if ($limit && is_numeric($limit)) {
			$db_limit = " LIMIT " . $limit;
		}

		$status = $this->query("DELETE FROM `" . $table . "`" . $db_where . $db_limit . ";");

		if (! $status) {
			$this->api->logger->error("DELETE FROM `" . $table . "`" . $db_where . $db_limit . ";");
		}

		return $status;
	}



	/**
	 * Truncate database table
	 *
	 * @param string $table
	 */
	public function truncate(string $table): mysqli_result|bool
	{
		if (! empty($table)) {
			return $this->query("TRUNCATE TABLE `" . $table . "`;");
		}

		return false;
	}



}
