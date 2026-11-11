<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
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
	public string $langId;


	/**
	 * Lower case language id
	 */
	public string $lang;



	/**
	 * Constructor
	 */
	public function __construct(string $langId, ParksAPI $api)
	{

		// Api instance
		$this->api = $api;

		// Load language file
		if (! empty($langId) && in_array($langId, $this->api->config['available_languages']) && file_exists($this->api->config['absolute_path'] . '/lang/' . strtolower($langId) . '.php')) {
			$labels = require($this->api->config['absolute_path'] . '/lang/' . strtolower($langId) . '.php');
		}

		// Load default language file
		elseif (file_exists($this->api->config['absolute_path'] . '/lang/de.php')) {
			$labels = require($this->api->config['absolute_path'] . '/lang/de.php');
			$langId = 'de';
		}

		// Error, no language file found
		else {
			$this->api->abort('The language file does not exist.');
		}

		// Check language labels
		if (! is_array($labels)) {
			$this->api->abort('Your language file does not appear to be formatted correctly.');
		}

		// Set labels
		$this->labels = $labels;

		// Set language id
		$this->langId = $langId;
		$this->lang = strtolower($langId);

	}



	/**
	 * Get language string
	 */
	public function get(string $key): string
	{
		if (isset($this->labels[$key])) {
			return $this->labels[$key];
		}

		return $key;
	}



}
