<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/


class ParksLog
{


	/**
	 * SDK instance
	 */
	public ParksSDK $sdk;


	/**
	 * Logfile path
	 */
	private string $logfile;



	/**
	 * Constructor
	 */
	public function __construct(ParksSDK $sdk)
	{

		$this->sdk = $sdk;

		// Set log path
		$this->logfile = $this->sdk->config['absolute_path'] . '/' . $this->sdk->config['log_directory'] . date("Y_m_d") . '.log';

	}



	/**
	 * Log error message
	 */
	public function error(string $message): void
	{
		$this->log("ERROR", $message);
	}



	/**
	 * Log info message
	 */
	public function info(string $message): void
	{
		$this->log("INFO", $message);
	}



	/**
	 * Log message
	 */
	private function log(string $level, string $message): void
	{
		$line = date("Y-m-d H:i:s") . "\t" . $level . "\t" . $message . "\n";
		file_put_contents($this->logfile, $line, FILE_APPEND);
	}


}
