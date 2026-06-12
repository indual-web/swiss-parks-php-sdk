<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Logger
|
*/


class ParksLog
{


	/**
	 * API
	 */
	public ParksAPI $api;


	/**
	 * Logfile path
	 */
	private string $logfile;



	/**
	 * Constructor
	 *
	 * @param ParksAPI $api
	 */
	public function __construct(ParksAPI $api)
	{

		// Api instance
		$this->api = $api;

		// Set log path
		$this->logfile = $this->api->config['absolute_path'] . '/' . $this->api->config['log_directory'] . date("Y_m_d") . '.log';

	}



	/**
	 * Log error message
	 *
	 * @param string $message
	 */
	public function error(string $message): void
	{
		$this->_log("ERROR", $message);
	}



	/**
	 * Log info message
	 *
	 * @param string $message
	 */
	public function info(string $message): void
	{
		$this->_log("INFO", $message);
	}



	/**
	 * Log message
	 *
	 * @param string $level
	 * @param string $message
	 */
	private function _log(string $level, string $message): void
	{
		$line = date("Y-m-d H:i:s") . "\t" . $level . "\t" . $message . "\n";
		file_put_contents($this->logfile, $line, FILE_APPEND);
	}



}
