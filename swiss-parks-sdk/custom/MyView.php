<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Extended view
|
*/


class MyView extends ParksView {


	/**
	 * Constructor
	 */
	public function __construct(ParksAPI $api) {
		parent::__construct($api);

		// Overwrite attributes here, like script_url, script_url_with_params ...

	}



	/**
	 * Overwrite template data
	 * Overwrite template data before they are loaded
	 */
	public function overwrite_template_data(array $template_data, object $offer): array {

		// Overwrite template data
		// Example:
		// $template_data['OFFER_EVENT_DETAIL'] = 'Test';

		return $template_data;
	}



	/*
	 * Overwriting methods:
	 * ---
	 * Copy, paste and modify methods from ParksView.php
	 *
	*/


}