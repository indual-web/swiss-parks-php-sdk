<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| SQLite query result
|
*/


class ParksSQLiteResult
{


	/**
	 * Number of rows in the result
	 */
	public int $numRows;


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
		$this->numRows = count($this->rows);

	}



	/**
	 * Fetch next row as associative array
	 */
	public function fetchAssoc(): ?array
	{

		if ($this->cursor >= $this->numRows) {
			return null;
		}

		return $this->rows[$this->cursor++];
	}



	/**
	 * Fetch next row as associative array
	 */
	public function fetchArray(): ?array
	{

		return $this->fetchAssoc();
	}



	/**
	 * Fetch next row as object
	 */
	public function fetchObject(): ?object
	{

		$row = $this->fetchAssoc();

		return ($row === null) ? null : (object) $row;
	}


}
