<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/

namespace SwissParks\Sdk;

use DateTime;
use Stringable;


class Date
{


	/**
	 * Format an offer date range (and optional time list) for detail/list output
	 */
	public static function formatOfferDate(array $date, Language $lang): string {

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
		$return .= '<strong>' . self::formatOfferDateLabel($date['date_from']['date'], $lang) . '</strong> ';

		// Show all times
		if (! empty($date['times'])) {
			$return .= self::formatOfferTimes($date['times']);
		}

		// Show time from
		elseif (! empty($date['date_from']['hour']) && ($date['date_from']['hour'] != '00')) {
			$return .= $date['date_from']['hour'] . ":" . $date['date_from']['minute'];
		}

		if (empty($date['times'])) {

			// Single range (no discrete time list): append "to" date/time when it differs
			if (! empty($date['date_to']) && ! empty($date['date_to']['date']) && ($date['date_from'] != $date['date_to'])) {

				// Show the connector when the end day or a non-midnight end time differs
				if (($date['date_from']['date'] != $date['date_to']['date']) || (! empty($date['date_to']['hour']) && ($date['date_to']['hour'] != '00'))) {
					$return .= ' ' . $lang->get('general_to') . ' ';
				}

				if ($date['date_from']['date'] != $date['date_to']['date']) {
					$return .= '<strong>' . self::formatOfferDateLabel($date['date_to']['date'], $lang) . '</strong> ';
				}

				if (! empty($date['date_to']['hour']) && ($date['date_to']['hour'] != '00')) {
					$return .= $date['date_to']['hour'] . ":" . $date['date_to']['minute'];
				}

			}

		}

		return $return;
	}



	/**
	 * Format a date label for offer output, optionally with a short weekday
	 */
	public static function formatOfferDateLabel(string $date, Language $lang): string {

		if (($lang->sdk->config['display_weekday_short'] ?? true) === true) {
			return self::formatDateWithShortWeekday($date, $lang);
		}

		$datetime = self::parseOfferDate($date);
		if ($datetime === false) {
			return $date;
		}

		return $datetime->format('d.m.Y');
	}



	/**
	 * Prepend a translated short weekday to a d.m.Y or Y-m-d date string
	 */
	public static function formatDateWithShortWeekday(string $date, Language $lang): string {

		$datetime = self::parseOfferDate($date);
		if ($datetime === false) {
			return $date;
		}

		// French short weekdays use a space; other languages use a comma
		$separator = ($lang->lang === 'fr') ? ' ' : $lang->get('cal_weekday_separator');

		return $lang->get('weekday_short_' . $datetime->format('N')) . $separator . $datetime->format('d.m.Y');
	}



	/**
	 * Parse a d.m.Y or Y-m-d date string
	 */
	private static function parseOfferDate(string $date): DateTime|false {

		if ($date === '') {
			return false;
		}

		$datetime = DateTime::createFromFormat('d.m.Y', $date);
		if ($datetime === false) {
			$datetime = DateTime::createFromFormat('Y-m-d', $date);
		}

		return $datetime;
	}



	/**
	 * Split a Y-m-d H:i:s value into date, hour and minute (empty array for null/empty)
	 */
	public static function splitDateTime(?string $dateTime): array {

		$return = [];

		if (! empty($dateTime)) {
			$return['date'] = substr($dateTime, 0, 10);
			$return['hour'] = substr($dateTime, 11, 2);
			$return['minute'] = substr($dateTime, 14, 2);
		}

		return $return;
	}



	/**
	 * Format a Y-m-d (H:i:s) value as d.m.Y, optionally with time or as timestamp
	 */
	public static function formatDate(mixed $value, bool $time = false, bool $ts = false): string|int {

		if (! empty($value)) {
			$date = substr($value, 0, 10);
			$dateTime = '';
			if ($time != false) {
				$dateTime = ' ' . substr($value, 11);
			}

			$dateExplode = explode("-", $date);

			// Zero-dates (0000-00-00) and incomplete values must not become 01.01.1970
			if (count($dateExplode) < 3 || (intval($dateExplode[0]) == 0) && (intval($dateExplode[1]) == 0) && (intval($dateExplode[2]) == 0)) {
				return '';
			}

			$dateExplodeTs = mktime(0, 0, 0, $dateExplode[1], $dateExplode[2], $dateExplode[0]);
			if ($ts == true) {
				return $dateExplodeTs;
			}

			return date('d.m.Y', $dateExplodeTs) . $dateTime;
		}

		return '';
	}



	/**
	 * Normalize a date/time value for storage and SQL comparison (empty string when invalid)
	 */
	public static function normalizeDateTime(mixed $value, string $format = 'Y-m-d H:i:s'): string {

		// XML attributes arrive as SimpleXMLElement, which is Stringable
		$datetime = (is_string($value) || $value instanceof Stringable) ? date_create((string) $value) : false;

		return ($datetime !== false) ? $datetime->format($format) : '';
	}



	/**
	 * Show multiple time data
	 */
	public static function formatOfferTimes(string $data): string {

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
	 */
	public static function splitHoursAndMinutes(int $totalMinutes): array {

	    if ($totalMinutes >= 1) {

			// Split total minutes into hours and minutes
		    $hours = floor($totalMinutes / 60);
			$minutes = ($totalMinutes % 60);

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
	 */
	public static function adjustDateFrom(string $dateFrom = '', string $dateTo = '', string $filterFrom = ''): string {

		if (! empty($dateFrom) && ! empty($dateTo)) {

			// Init dates
			$dateFrom = new DateTime($dateFrom);
			$dateTo = new DateTime($dateTo);
			$filterDate = new DateTime($filterFrom);
			$today = new DateTime();

			// Multiple days
			if ($dateFrom->format('Y-m-d') != $dateTo->format('Y-m-d')) {

				// Overwrite with filter date
				if (! empty($filterFrom) && ($filterDate > $dateFrom) && ($filterDate < $dateTo)) {
					return $filterDate->format('Y-m-d');
				}

				// Set date to current day, if it takes multiple days and it started in the past
				else if (($today > $dateFrom) && ($dateTo > $today)) {
					return $today->format('Y-m-d');
				}

			}

			return $dateFrom->format('Y-m-d H:i');
		}

		return $dateFrom;
	}



	/**
	 * Activities: Get time required info and return formatted value
	 */
	public static function formatActivityTimeRequired(object $offer, Language $lang, bool $shortLabels = false): string {

		// Init
		$return = '';

		// Format minutes
		if ( ! empty($offer->time_required_minutes)) {

			// Split hours and minutes
			$timeRequired = self::splitHoursAndMinutes($offer->time_required_minutes);

			// Set hours
			$hours = '';
			if (! empty($timeRequired['hours']) && ($timeRequired['hours'] > 0)) {

				// Set label for hours (singular, plural)
				$labelHours = 'h';
				if ($shortLabels == false) {
					$labelHours = ($timeRequired['hours'] == 1) ? $lang->get('offer_hour') : $lang->get('offer_hours');
				}

				// Set hours
				$hours = intval($timeRequired['hours']) . ' ' . $labelHours;

			}

			// Set minutes
			$minutes = '';
			if (! empty($timeRequired['minutes']) && ($timeRequired['minutes'] > 0)) {

				// Set label for hours (singular, plural)
				$labelMinutes = 'min';
				if ($shortLabels == false) {
					$labelMinutes = ($timeRequired['minutes'] == 1) ? $lang->get('offer_minute') : $lang->get('offer_minutes');
				}

				// Set minutes
				$minutes = ' ' . intval($timeRequired['minutes']) . ' ' . $labelMinutes;

			}

			// Minutes carry a leading space for the hours separator; drop it when hours are missing
			$return = trim($hours . $minutes);

		} 

		// Format time category
		else if ( ! empty($offer->time_required)) {

			// Return category
			$return = $offer->time_required;

		}

		return $return;
	}


}
