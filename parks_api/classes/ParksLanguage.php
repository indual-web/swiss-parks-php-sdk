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
	public $api;


	/**
	 * labels for current language
	 */
	private $labels = [];


	/**
	 * language id
	 */
	public $lang_id;


	/**
	 * Lower case language id
	 */
	public $lang;



	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $lang_id
	 * @param object $api
	 * @return void
	 */
	function __construct($lang_id, $api)
	{

		// Api instance
		$this->api = $api;

		// Load language file
		if (! empty($lang_id) && in_array($lang_id, $this->api->config['available_languages']) && file_exists($this->api->config['absolute_path'] . '/language/' . strtolower($lang_id) . '.php')) {
			require($this->api->config['absolute_path'] . '/language/' . strtolower($lang_id) . '.php');
			$this->lang = strtolower($lang_id);
		}

		// Load default language file
		elseif (file_exists($this->api->config['absolute_path'] . '/language/de.php')) {
			require($this->api->config['absolute_path'] . '/language/de.php');
			$lang_id = 'de';
		}

		// Error, no language file found
		else {
			echo 'The language file does not exist.';
			exit();
		}

		// Check language labels
		if (! isset($lang) || ! is_array($lang)) {
			echo 'Your language file does not appear to be formatted correctly.';
			exit();
		}

		// Set labels
		$this->labels = $lang;

		// Set language id
		$this->lang_id = $lang_id;

	}



	/**
	 * Get language string
	 *
	 * @access public
	 * @param string $key
	 * @return string
	 */
	public function get($key)
	{
		if (isset($this->labels[$key])) {
			return $this->labels[$key];
		}

		return $key;
	}



}