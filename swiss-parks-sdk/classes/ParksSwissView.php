<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/


class ParksSwissView extends ParksView {



	/**
	 * Constructor
	 */
	public function __construct(ParksSDK $sdk) {
		parent::__construct($sdk);
	}



	/**
	 * Overwrite template data
	 * Overwrite template data before they are loaded
	 */
	public function overwriteTemplateData(array $templateData, object $offer): array {

		// Get offer dates
		$templateData['OFFER_DATES'] = $this->getOfferDates($offer);

		// Overwrite events
		$templateData['OFFER_EVENT_LOCATION'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_event_location'), $offer->institution, 'block event_location'));
		$templateData['OFFER_EVENT_DETAIL'] = $this->getDetailEvent($offer);

		// Overwrite activities
		$templateData['OFFER_ACTIVITY_DETAIL'] = $this->getDetailActivity($offer);
		$templateData['OFFER_ACTIVITY_ARRIVAL'] = trim(parent::renderDetailBlock('', parent::getRouteStartStop($offer), 'block arrival rwd-info-block'));
		$templateData['OFFER_ACTIVITY_ROUTE'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_route_info'), parent::getRouteDetails($offer), 'block route_info rwd-info-block'));

		// Overwrite products
		$templateData['OFFER_PRODUCT_DETAIL'] = $this->getDetailProduct($offer);

		// Overwrite bookings
		$templateData['OFFER_BOOKING_DETAIL'] = $this->getDetailBooking($offer);

		return $templateData;
	}



	/**
	 * Get event detail
	 */
	protected function getDetailEvent(object $offer): string {

		// Load template data
		$templateData['OFFER_ADDITIONAL_INFO'] = parent::renderAdditionalInfos($offer);
		$templateData['OFFER_EVENT_DATE_DETAILS'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_date_details'), $offer->details, 'block date_details'));
		$templateData['OFFER_EVENT_LOCATION_DETAILS'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_location_details'), $offer->location_details, 'block location_details'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$templateData['OFFER_EVENT_TRANSPORT'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$templateData['OFFER_EVENT_PRICE'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_price'), $offer->price, 'block price'));

		// Compile template data
		return parent::compileOutput($templateData);

	}


	/**
	 * Get product detail
	 */
	protected function getDetailProduct(object $offer): string {

		// Load template data
		if (! empty($offer->online_shop_enabled)) {

			// Show article price
			$templateData['OFFER_PRODUCT_PRICE'] = '
				<div class="block product_price">
					<div class="description">
						<h2>' . $this->sdk->lang->get('offer_product_price') . '</h2>
						<div class="price">
							<span class="currency">CHF</span>
							<span class="value">' . $offer->online_shop_price . '</span>
						</div>
					</div>
				</div>

				<div class="block product_price">
					<div class="description">
						<h2>' . $this->sdk->lang->get('offer_product_payment_modalities') . '</h2>
						<div class="order_information payment_info">' . $offer->online_shop_payment_terms . '</div>
					</div>
				</div>

				<div class="block product_price">
					<div class="description">
						<h2>' . $this->sdk->lang->get('offer_product_delivery_conditions') . '</h2>
						<div class="order_information delivery_info">' . $offer->online_shop_delivery_conditions . '</div>
					</div>
				</div>
			'; 

		}
		else {
			$templateData['OFFER_PRODUCT_PRICE'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_price'), $offer->price, 'block price'));
		}
		
		$templateData['OFFER_ADDITIONAL_INFO'] = parent::renderAdditionalInfos($offer);
		if (empty($offer->online_shop_enabled)) {
			$templateData['OFFER_DATES'] = $this->getOfferDates($offer);
		}
		$templateData['OFFER_PRODUCT_OPENING_HOURS'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_opening_hours'), $offer->opening_hours, 'block opening_hours'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$templateData['OFFER_PRODUCT_PUBLIC_TRANSPORT'] = trim($this->renderDetailBlock($this->sdk->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb" title="' . $this->sdk->lang->get('offer_link_sbb') . '">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$templateData['OFFER_PRODUCT_PUBLIC_TRANSPORT'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$templateData['OFFER_PRODUCT_INFRASTRUCTURE'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_infrastructure'), parent::getDetailInfrastructure($offer), 'block infrastructure'));

		// Compile template data
		return parent::compileOutput($templateData);

	}



	/**
	 * Get booking detail
	 */
	protected function getDetailBooking(object $offer): string {

		// Load template data
		$templateData['OFFER_ADDITIONAL_INFO'] = parent::renderAdditionalInfos($offer);
		$templateData['OFFER_BOOKING_GROUPS'] = parent::getDetailGroups($offer);
		$templateData['OFFER_BOOKING_BENEFITS'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_benefits'), $offer->benefits, 'block benefits'));
		$templateData['OFFER_BOOKING_REQUIREMENTS'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_requirements'), $offer->requirements, 'block requirements'));
		if (! empty($offer->public_transport_stop) && (strlen($offer->public_transport_stop) >= $this->config['min_chars_sbb_link'])) {
			$templateData['OFFER_BOOKING_TRANSPORT'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_public_transport_stop'), $offer->public_transport_stop . ' <a href="' . $this->sbbLink . '?nach=' . urlencode($offer->public_transport_stop) . '" target="_blank" class="sbb">' . $this->sdk->lang->get('offer_timetable_sbb') . '</a>', 'block public_transport_stop'));
		}
		$templateData['OFFER_BOOKING_PRICE'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_price'), $offer->price, 'block price'));
		$templateData['OFFER_BOOKING_ACCOMMODATIONS'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_accommodation'), parent::getAccommodations($offer), 'block accommodations'));

		// Compile template data
		return parent::compileOutput($templateData);

	}


	/**
	 * Get activity detail
	 */
	protected function getDetailActivity(object $offer): string {

		// Load template data
		$templateData['OFFER_ADDITIONAL_INFO'] = parent::renderAdditionalInfos($offer);
		$templateData['OFFER_ACTIVITY_MATERIAL_RENT'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_material_rent'), $offer->material_rent, 'block material_rent'));
		$templateData['OFFER_ACTIVITY_SIGNALIZATION'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_signalization'), $offer->signalization, 'block signalization'));
		$templateData['OFFER_ACTIVITY_SAFETY_INSTRUCTIONS'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_safety_instructions'), $offer->safety_instructions, 'block safety_instructions'));
		$templateData['OFFER_ACTIVITY_CATERING'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_catering_informations'), $offer->catering_informations, 'block catering_informations'));
		$templateData['OFFER_ACTIVITY_PRICE'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_price'), $offer->price, 'block price'));
		$templateData['OFFER_ACTIVITY_INFRASTRUCTURE'] = trim(parent::renderDetailBlock($this->sdk->lang->get('offer_infrastructure'), parent::getDetailInfrastructure($offer), 'block infrastructure'));

		// Compile template data
		return parent::compileOutput($templateData);

	}


}
