<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/

namespace SwissParks\Sdk;


class Log
{


	/**
	 * SDK instance
	 */
	public SwissParksSdk $sdk;


	/**
	 * Logfile path
	 */
	private string $logfile;



	/**
	 * Constructor
	 */
	public function __construct(SwissParksSdk $sdk)
	{

		$this->sdk = $sdk;

		// Set log path (relative paths are relative to swiss-parks-sdk/)
		$logDirectory = $this->sdk->config['log_directory'];
		if (substr($logDirectory, 0, 1) !== '/') {
			$logDirectory = $this->sdk->config['absolute_path'] . '/' . $logDirectory;
		}
		$this->logfile = $logDirectory . date("Y_m_d") . '.log';

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

		// Own log directories (e.g. outside vendor/) may not exist yet
		$directory = dirname($this->logfile);
		if (! is_dir($directory)) {
			mkdir($directory, 0775, true);
		}

		$line = date("Y-m-d H:i:s") . "\t" . $level . "\t" . $message . "\n";
		file_put_contents($this->logfile, $line, FILE_APPEND);
	}


}
