<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Global date helper functions
|
*/


/**
 * Show date
 *
 * @access public
 * @param array $date
 * @param object $lang
 * @return string
 */
function parks_show_date($date, $lang) {
	$return = '';

	// Init date
	if (! empty($date['date_from']) && ! isset($date['date_from']['date'])) {

		// Date from
		$date['date_from'] = [
			'date' => date('d.m.Y', strtotime($date['date_from'])),
			'hour' => date('H', strtotime($date['date_from'])),
			'minute' => date('i', strtotime($date['date_from'])),
		];

		// Date to
		if (! empty($date['date_to'])) {
			$date['date_to'] = [
				'date' => date('d.m.Y', strtotime($date['date_to'])),
				'hour' => date('H', strtotime($date['date_to'])),
				'minute' => date('i', strtotime($date['date_to'])),
			];
		}

	}

	// Date from
	$return .= '<strong>'.$date['date_from']['date'].'</strong> ';

	// Show all times
	if (! empty($date['times'])) {
		$return .= parks_show_times($date['times']);
	}

	// Show time from
	elseif (! empty($date['date_from']['hour']) && ($date['date_from']['hour'] != '00')) {
		$return .= $date['date_from']['hour'].":".$date['date_from']['minute'];
	}

	if (empty($date['times'])) {

		// Date to
		if (! empty($date['date_to']) && ! empty($date['date_to']['date']) && ($date['date_from'] != $date['date_to'])) {

			if (($date['date_from']['date'] != $date['date_to']['date']) || (! empty($date['date_to']['hour']) && ($date['date_to']['hour'] != '00'))) {
				$return .= ' '.$lang->get('general_to').' ';
			}

			if ($date['date_from']['date'] != $date['date_to']['date']) {
				$return .= '<strong>'.$date['date_to']['date'].'</strong> ';
			}
			
			if (! empty($date['date_to']['hour']) && ($date['date_to']['hour'] != '00')) {
				$return .= $date['date_to']['hour'].":".$date['date_to']['minute'];
			}

		}

	}

	return $return;
}



/**
 * Convert MySQL to a form format
 *
 * @access public
 * @param string $mysql_date
 * @return array
 */
function parks_mysql2form($mysql_date) {
	$return = [];

	if (! empty($mysql_date)) {
		$return['date'] = substr($mysql_date, 0, 10);
		$return['hour'] = substr($mysql_date, 11, 2);
		$return['minute'] = substr($mysql_date, 14, 2);
	}

	return $return;
}



/**
 * Convert MySQL to a date format
 *
 * @access public
 * @param mixed $mysql_date
 * @param bool $time (default: false)
 * @param bool $ts (default: false)
 * @return string
 */
function parks_mysql2date($mysql_date, $time = false, $ts = false) {
	if (! empty($mysql_date)) {
		$date = substr($mysql_date, 0, 10);
		$date_time = '';
		if ($time != false) {
			$date_time = ' '.substr($mysql_date, 11);
		}

		$date_explode = explode("-", $date);

		if (count($date_explode) < 3 || (intval($date_explode[0]) == 0) && (intval($date_explode[1]) == 0) && (intval($date_explode[2]) == 0)) {
			return '';
		}

		$date_explode_ts = mktime(0, 0, 0, $date_explode[1], $date_explode[2], $date_explode[0]);
		if ($ts == true) {
			return $date_explode_ts;
		}

		return date('d.m.Y', $date_explode_ts).$date_time;
	}

	return '';
}



/**
 * Show multiple time data
 *
 * @param string $data
 * @return string
 */
function parks_show_times($data) {

	// Init
	$times = [];

	// Check data
	if (($data != '') && ($data != '00:00 - 00:00')) {
		
		// Split times
		$data = explode(',', $data);

		// Remove empty times
		foreach ($data as $time) {
			if ($time != '00:00 - 00:00') {

				// Remove empty time
				$time = str_replace(' - 00:00', '', $time);

				// Add time
				$times[] = $time;
				
			}
		}

		// Reduce displayed amount of times
		if (count($times) > 3) {
			$times = [$times[0], $times[1], "…", array_reverse($times)[0]];
		}
	}

	return implode(' / ', $times);
}



/**
 * Split hours and minutes by minutes
 *
 * @param int $total_minutes
 * @return array
 */
function parks_split_hours_and_minutes($total_minutes) {
    if ($total_minutes >= 1) {

		// Split total minutes into hours and minutes
	    $hours = floor($total_minutes / 60);
		$minutes = ($total_minutes % 60);
		
		// Return result
		return [
			'hours' => $hours,
			'minutes' => $minutes
		];

	}
	
	return [];
}



/**
 * Adjust event start date
 * - If it has already started: Sets it to the current date
 * - If a filter was set: Sets it to the filter date
 * - If the event is in the future: No changes
 *
 * @param string $date_from
 * @param string $date_to
 * @param string $filter_from
 * @return string
 */
function parks_adjust_date_from($date_from = '', $date_to = '', $filter_from = '') {
	if (! empty($date_from) && ! empty($date_to)) {

		// Init dates
		$date_from = new DateTime($date_from);
		$date_to = new DateTime($date_to);
		$filter_date = new DateTime($filter_from);
		$today = new DateTime();

		// Multiple days
		if ($date_from->format('Y-m-d') != $date_to->format('Y-m-d')) {
			
			// Overwrite with filter date
			if (! empty($filter_from) && ($filter_date > $date_from) && ($filter_date < $date_to)) {
				return $filter_date->format('Y-m-d');
			}

			// Set date to current day, if it takes multiple days and it started in the past
			else if (($today > $date_from) && ($date_to > $today)) {
				return $today->format('Y-m-d');
			}

		}

		return $date_from->format('Y-m-d H:i');
	}

	return $date_from;
}



/**
 * Activities: Get time required info and return formatted value
 *
 * @param object $offer
 * @param object $lang
 * @param bool $short_labels (default: false)
 * @return string $return
 */
function activity_get_time_required($offer, $lang, $short_labels = false) {

	// Init
	$return = '';

	// Format minutes
	if ( ! empty($offer->time_required_minutes)) {

		// Split hours and minutes
		$time_required = parks_split_hours_and_minutes($offer->time_required_minutes);

		// Set hours
		$hours = '';
		if (! empty($time_required['hours']) && ($time_required['hours'] > 0)) {

			// Set label for hours (singular, plural)
			$label_hours = 'h';
			if ($short_labels == false) {
				$label_hours = ($time_required['hours'] == 1) ? $lang->get('offer_hour') : $lang->get('offer_hours');
			}

			// Set hours
			$hours = intval($time_required['hours']).' '.$label_hours;

		}
		
		// Set minutes
		$minutes = '';
		if (! empty($time_required['minutes']) && ($time_required['minutes'] > 0)) {

			// Set label for hours (singular, plural)
			$label_minutes = 'min';
			if ($short_labels == false) {
				$label_minutes = ($time_required['minutes'] == 1) ? $lang->get('offer_minute') : $lang->get('offer_minutes');
			}

			// Set minutes
			$minutes = ' '.intval($time_required['minutes']).' '.$label_minutes;

		}

		// Return hours and minutes
		$return = $hours.$minutes; 

	} 

	// Format time category
	else if ( ! empty($offer->time_required)) {

		// Return category
		$return = $offer->time_required;

	}

	return $return;
}