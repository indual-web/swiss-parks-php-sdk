<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| SQLite query result
|
*/


class ParksSQLiteResult
{


	/**
	 * Number of rows in the result
	 */
	public int $num_rows;


	/**
	 * Result rows (associative arrays)
	 */
	private array $rows;


	/**
	 * Internal row cursor
	 */
	private int $cursor = 0;



	/**
	 * Constructor
	 */
	public function __construct(array $rows)
	{

		$this->rows = array_values($rows);
		$this->num_rows = count($this->rows);

	}



	/**
	 * Fetch next row as associative array
	 */
	public function fetch_assoc(): ?array
	{

		if ($this->cursor >= $this->num_rows) {
			return null;
		}

		return $this->rows[$this->cursor++];
	}



	/**
	 * Fetch next row as associative array
	 */
	public function fetch_array(): ?array
	{

		return $this->fetch_assoc();
	}



	/**
	 * Fetch next row as object
	 */
	public function fetch_object(): ?object
	{

		$row = $this->fetch_assoc();

		return ($row === null) ? null : (object) $row;
	}


}
