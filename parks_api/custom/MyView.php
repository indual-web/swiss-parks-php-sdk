<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Extended view
|
*/


class MyView extends ParksView {


	/**
	 * Constructor
	 *
	 * @access public
	 * @param  array $api
	 * @return void
	 */
	function __construct($api) {
		parent::__construct($api);

		// Overwrite attributes here, like script_url, script_url_with_params ...

	}



	/**
	 * Overwrite template data
	 * Overwrite template data before they are loaded
	 *
	 * @access public
	 * @param mixed $template_data
	 * @return void
	 */
	public function overwrite_template_data($template_data, $offer) {

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