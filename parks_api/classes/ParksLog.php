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
	public $api;


	/**
	 * Logfile path
	 */
	private $logfile;



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
		$this->api = $api;

		// Set log path
		$this->logfile = $this->api->config['absolute_path'] . '/' . $this->api->config['log_directory'] . date("Y_m_d") . '.log';

	}



	/**
	 * Log error message
	 *
	 * @access public
	 * @param string $message
	 * @return void
	 */
	public function error($message)
	{
		$this->_log("ERROR", $message);
	}



	/**
	 * Log info message
	 *
	 * @access public
	 * @param string $message
	 * @return void
	 */
	public function info($message)
	{
		$this->_log("INFO", $message);
	}



	/**
	 * Log message
	 *
	 * @access private
	 * @param int $level
	 * @param string $message
	 * @return void
	 */
	private function _log($level, $message)
	{
		$line = date("Y-m-d H:i:s") . "\t" . $level . "\t" . $message . "\n";
		file_put_contents($this->logfile, $line, FILE_APPEND);
	}



}