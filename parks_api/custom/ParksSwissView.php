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


class ParksSwissView extends ParksView {



	/**
	 * Constructor
	 *
	 * @access public
	 * @param  array $api
	 * @return void
	 */
	function __construct($api) {
		parent::__construct($api);
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

		// Get offer dates
		$template_data['OFFER_DATES'] = $this->_get_offer_dates($offer);

		// Overwrite events
		$template_data['OFFER_EVENT_LOCATION'] = trim($this->_show_text($this->api->lang->get('offer_event_location'), $offer->institution, 'block event_location'));
		$template_data['OFFER_EVENT_DETAIL'] = $this->_get_detail_event($offer);

		// Overwrite activities
		$template_data['OFFER_ACTIVITY_DETAIL'] = $this->_get_detail_activity($offer);
		$template_data['OFFER_ACTIVITY_ARRIVAL'] = trim(parent::_show_text('', parent::_get_route_start_stop($offer), 'block arrival rwd-info-block'));
		$template_data['OFFER_ACTIVITY_ROUTE'] = trim(parent::_show_text($this->api->lang->get('offer_route_info'), parent::_get_route_details($offer), 'block route_info rwd-info-block'));

		// Overwrite products
		$template_data['OFFER_PRODUCT_DETAIL'] = $this->_get_detail_product($offer);

		// Overwrite bookings
		$template_data['OFFER_BOOKING_DETAIL'] = $this->_get_detail_booking($offer);

		return $template_data;
	}



	/**
	 * Get event detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_event($offer) {

		// Load template data
		$template_data['OFFER_ADDITIONAL_INFO'] = parent:: _prepare_additional_infos($offer);
		$template_data['OFFER_EVENT_DATE_DETAILS'] = trim(parent::_show_text($this->api->lang->get('offer_date_details'), $offer->details, 'block date_details'));
		$template_data['OFFER_EVENT_LOCATION_DETAILS'] = trim(parent::_show_text($this->api->lang->get('offer_location_details'), $offer->location_details, 'block location_details'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$template_data['OFFER_EVENT_TRANSPORT'] = trim(parent::_show_text($this->api->lang->get('offer_public_transport_stop'), $offer->public_transport_stop.' <a href="'.$this->sbb_link.'?nach='.urlencode($offer->public_transport_stop).'" target="_blank" class="sbb">'.$this->api->lang->get('offer_timetable_sbb').'</a>', 'block public_transport_stop'));
		}
		$template_data['OFFER_EVENT_PRICE'] = trim(parent::_show_text($this->api->lang->get('offer_price'), $offer->price, 'block price'));

		// Compile template data
		return parent::_compile_output($template_data);

	}


	/**
	 * Get product detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_product($offer) {

		// Load template data
		if (! empty($offer->online_shop_enabled)) {

			// Show article price
			$template_data['OFFER_PRODUCT_PRICE'] = '
				<div class="block product_price">
					<div class="description">
						<h2>'.$this->api->lang->get('offer_product_price').'</h2>
						<div class="price">
							<span class="currency">CHF</span>
							<span class="value">'.$offer->online_shop_price.'</span>
						</div>
					</div>
				</div>

				<div class="block product_price">
					<div class="description">
						<h2>'.$this->api->lang->get('offer_product_payment_modalities').'</h2>
						<div class="order_information payment_info">'.$offer->online_shop_payment_terms.'</div>
					</div>
				</div>

				<div class="block product_price">
					<div class="description">
						<h2>'.$this->api->lang->get('offer_product_delivery_conditions').'</h2>
						<div class="order_information delivery_info">'.$offer->online_shop_delivery_conditions.'</div>
					</div>
				</div>
			'; 

		}
		else {
			$template_data['OFFER_PRODUCT_PRICE'] = trim($this->_show_text($this->api->lang->get('offer_price'), $offer->price, 'block price'));
		}
		
		$template_data['OFFER_ADDITIONAL_INFO'] = parent::_prepare_additional_infos($offer);
		if (empty($offer->online_shop_enabled)) {
			$template_data['OFFER_DATES'] = $this->_get_offer_dates($offer);
		}
		$template_data['OFFER_PRODUCT_OPENING_HOURS'] = trim(parent::_show_text($this->api->lang->get('offer_opening_hours'), $offer->opening_hours, 'block opening_hours'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$template_data['OFFER_PRODUCT_PUBLIC_TRANSPORT'] = trim($this->_show_text($this->api->lang->get('offer_public_transport_stop'), $offer->public_transport_stop.' <a href="'.$this->sbb_link.'?nach='.urlencode($offer->public_transport_stop).'" target="_blank" class="sbb" title="'.$this->api->lang->get('offer_link_sbb').'">'.$this->api->lang->get('offer_timetable_sbb').'</a>', 'block public_transport_stop'));
		}
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$template_data['OFFER_PRODUCT_PUBLIC_TRANSPORT'] = trim(parent::_show_text($this->api->lang->get('offer_public_transport_stop'), $offer->public_transport_stop.' <a href="'.$this->sbb_link.'?nach='.urlencode($offer->public_transport_stop).'" target="_blank" class="sbb">'.$this->api->lang->get('offer_timetable_sbb').'</a>', 'block public_transport_stop'));
		}
		$template_data['OFFER_PRODUCT_INFRASTRUCTURE'] = trim(parent::_show_text($this->api->lang->get('offer_infrastructure'), parent::_get_detail_infrastructure($offer), 'block infrastructure'));

		// Compile template data
		return parent::_compile_output($template_data);

	}



	/**
	 * Get booking detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_booking($offer) {

		// Load template data
		$template_data['OFFER_ADDITIONAL_INFO'] = parent:: _prepare_additional_infos($offer);
		$template_data['OFFER_BOOKING_GROUPS'] = parent::_get_detail_groups($offer);
		$template_data['OFFER_BOOKING_BENEFITS'] = trim(parent::_show_text($this->api->lang->get('offer_benefits'), $offer->benefits, 'block benefits'));
		$template_data['OFFER_BOOKING_REQUIREMENTS'] = trim(parent::_show_text($this->api->lang->get('offer_requirements'), $offer->requirements, 'block requirements'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$template_data['OFFER_BOOKING_TRANSPORT'] = trim(parent::_show_text($this->api->lang->get('offer_public_transport_stop'), $offer->public_transport_stop.' <a href="'.$this->sbb_link.'?nach='.urlencode($offer->public_transport_stop).'" target="_blank" class="sbb">'.$this->api->lang->get('offer_timetable_sbb').'</a>', 'block public_transport_stop'));
		}
		$template_data['OFFER_BOOKING_PRICE'] = trim(parent::_show_text($this->api->lang->get('offer_price'), $offer->price, 'block price'));
		$template_data['OFFER_BOOKING_ACCOMMODATIONS'] = trim(parent::_show_text($this->api->lang->get('offer_accommodation'), parent::_get_accommodations($offer), 'block accommodations'));

		// Compile template data
		return parent::_compile_output($template_data);

	}


	/**
	 * Get activity detail
	 *
	 * @access protected
	 * @param mixed $offer
	 * @return string
	 */
	protected function _get_detail_activity($offer) {

		// Load template data
		$template_data['OFFER_ADDITIONAL_INFO'] = parent:: _prepare_additional_infos($offer);
		$template_data['OFFER_ACTIVITY_MATERIAL_RENT'] = trim(parent::_show_text($this->api->lang->get('offer_material_rent'), $offer->material_rent, 'block material_rent'));
		$template_data['OFFER_ACTIVITY_SIGNALIZATION'] = trim(parent::_show_text($this->api->lang->get('offer_signalization'), $offer->signalization, 'block signalization'));
		$template_data['OFFER_ACTIVITY_SAFETY_INSTRUCTIONS'] = trim(parent::_show_text($this->api->lang->get('offer_safety_instructions'), $offer->safety_instructions, 'block safety_instructions'));
		$template_data['OFFER_ACTIVITY_CATERING'] = trim(parent::_show_text($this->api->lang->get('offer_catering_informations'), $offer->catering_informations, 'block catering_informations'));
		$template_data['OFFER_ACTIVITY_PRICE'] = trim(parent::_show_text($this->api->lang->get('offer_price'), $offer->price, 'block price'));
		$template_data['OFFER_ACTIVITY_INFRASTRUCTURE'] = trim(parent::_show_text($this->api->lang->get('offer_infrastructure'), parent::_get_detail_infrastructure($offer), 'block infrastructure'));

		// Compile template data
		return parent::_compile_output($template_data);

	}


}