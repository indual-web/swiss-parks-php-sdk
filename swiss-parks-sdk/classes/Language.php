<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/

namespace SwissParks\Sdk;


class Language
{


	/**
	 * SDK instance
	 */
	public SwissParksSdk $sdk;


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
	public function __construct(string $langId, SwissParksSdk $sdk)
	{

		$this->sdk = $sdk;

		// Load language file
		if (! empty($langId) && in_array($langId, $this->sdk->config['available_languages']) && file_exists($this->sdk->config['absolute_path'] . '/lang/' . strtolower($langId) . '.php')) {
			$labels = require($this->sdk->config['absolute_path'] . '/lang/' . strtolower($langId) . '.php');
		}

		// Load default language file
		elseif (file_exists($this->sdk->config['absolute_path'] . '/lang/de.php')) {
			$labels = require($this->sdk->config['absolute_path'] . '/lang/de.php');
			$langId = 'de';
		}

		// Error, no language file found
		else {
			$this->sdk->abort('The language file does not exist.');
		}

		// Check language labels
		if (! is_array($labels)) {
			$this->sdk->abort('Your language file does not appear to be formatted correctly.');
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
