<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Language class
|
*/


class ParksLanguage
{


	/**
	 * API
	 */
	public ParksAPI $api;


	/**
	 * labels for current language
	 */
	private array $labels = [];


	/**
	 * language id
	 */
	public string $lang_id;


	/**
	 * Lower case language id
	 */
	public string $lang;



	/**
	 * Constructor
	 *
	 * @param string $lang_id
	 * @param ParksAPI $api
	 */
	public function __construct(string $lang_id, ParksAPI $api)
	{

		// Api instance
		$this->api = $api;

		// Load language file
		if (! empty($lang_id) && in_array($lang_id, $this->api->config['available_languages']) && file_exists($this->api->config['absolute_path'] . '/language/' . strtolower($lang_id) . '.php')) {
			$labels = require($this->api->config['absolute_path'] . '/language/' . strtolower($lang_id) . '.php');
		}

		// Load default language file
		elseif (file_exists($this->api->config['absolute_path'] . '/language/de.php')) {
			$labels = require($this->api->config['absolute_path'] . '/language/de.php');
			$lang_id = 'de';
		}

		// Error, no language file found
		else {
			echo 'The language file does not exist.';
			exit();
		}

		// Check language labels
		if (! is_array($labels)) {
			echo 'Your language file does not appear to be formatted correctly.';
			exit();
		}

		// Set labels
		$this->labels = $labels;

		// Set language id
		$this->lang_id = $lang_id;
		$this->lang = strtolower($lang_id);

	}



	/**
	 * Get language string
	 *
	 * @param string $key
	 */
	public function get(string $key): string
	{
		if (isset($this->labels[$key])) {
			return $this->labels[$key];
		}

		return $key;
	}



}
