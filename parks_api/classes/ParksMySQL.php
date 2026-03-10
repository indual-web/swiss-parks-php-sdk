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
	public $api;


	/**
	 * MySQL connection
	 */
	private $connection;


	/**
	 * MySQL Host
	 */
	protected $hostname;


	/**
	 * MySQL Username
	 */
	protected $username;


	/**
	 * MySQL Password
	 */
	protected $password;


	/**
	 * MySQL Database
	 */
	protected $database;


	/**
	 * Last error message
	 */
	protected $last_error;



	/**
	 * Constructor
	 *
	 * @access public
	 * @param object $api
	 * @return void
	 */
	function __construct($api)
	{

		// Api instance
		$this->api = (object)$api;

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
	 *
	 * @access private
	 * @return bool
	 */
	private function connect()
	{
		$this->connection = new mysqli($this->hostname, $this->username, $this->password, $this->database);

		if ($this->connection->connect_error) {
			die('Connect Error (' . $this->connection->connect_errno . ') ' . $this->connection->connect_error);
		}

		return true;
	}



	/**
	 * Get last mysql error
	 *
	 * @access public
	 * @return string
	 */
	public function get_last_error()
	{
		return mysqli_error($this->connection);
	}



	/**
	 * Query database
	 *
	 * @access public
	 * @param string $sql
	 * @return mixed
	 */
	public function query($sql)
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
	 * @param array $where
	 * @param array $joins
	 * @param array $select
	 * @param string $limit
	 * @param string $offset
	 * @param array $left_outer_joins
	 * @param string $order_by
	 * @return mixed
	 */
	public function get($table, $where = NULL, $joins = NULL, $select = NULL, $limit = NULL, $offset = NULL, $left_outer_joins = NULL, $order_by = NULL)
	{

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
	 * @param boolean $escape
	 * @return mixed
	 */
	public function insert($table, $fields, $escape = false)
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

			if ($value === NULL) {
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
	 * @param array $where
	 * @param boolean $escape
	 * @return mixed
	 */
	public function update($table, $fields, $where = NULL, $escape = false)
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

			if ($value === NULL) {
				$db_fields .= "`" . $key . "` = NULL, ";
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
	 * @param int $limit
	 * @return mixed
	 */
	public function delete($table, $where = [], $limit = false)
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
	 * @access public
	 * @param string $table
	 * @return mixed
	 */
	public function truncate($table)
	{
		if (! empty($table)) {
			return $this->query("TRUNCATE TABLE `" . $table . "`;");
		}

		return false;
	}



}