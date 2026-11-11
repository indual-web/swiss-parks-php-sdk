<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| ParksMap
|
*/


class ParksMap
{


	public static function formatOffersByCategoryJs(array $offers, bool $ignore_categories_with_subcategories = false): string {

		if (empty($offers)) {
			return '';
		}

		$categories_offers = [];

		// Populate categories with subcategories
		$categories_with_subcategories = [
			CATEGORY_EVENT,
			CATEGORY_PRODUCT,
				CATEGORY_INFORMATION,
				CATEGORY_SIGHT,
				CATEGORY_REGIONAL_PRODUCT,
				CATEGORY_GASTRONOMY_AND_ACCOMMODATION,
					CATEGORY_GASTRONOMY,
					CATEGORY_ACCOMMODATION,
				CATEGORY_INFRASTRUCTURE,
			CATEGORY_BOOKING,
			CATEGORY_ACTIVITY,
				CATEGORY_SUMMER_ACTIVITIES,
				CATEGORY_WINTER_ACTIVITIES,
			CATEGORY_PROJECT,
			CATEGORY_RESEARCH,
		];

		// Populate offers by categories
		foreach ($offers as $offer) {

			if (! empty($offer->categories) && is_array($offer->categories)) {
				foreach ($offer->categories as $category_id => $category) {

					// Skip categories with subcategories
					if ($ignore_categories_with_subcategories && in_array($category_id, $categories_with_subcategories)) {
						continue;
					}

					// Add offer to category
					$categories_offers[$category_id][] = $offer->offer_id;

				}
			}
		}

		$categories_offers_view = '';

		if (! empty($categories_offers)) {
			foreach ($categories_offers as $category_id => $offer_ids) {
				$categories_offers_view .= $category_id . ': [\'' . implode('\', \'', $offer_ids) . '\'],';
			}
		}

		return $categories_offers_view;
	}



	/**
	 * Resolve a language-specific map layer URL (CSV languages, then de → fr → it → en)
	 */
	public static function resolveI18nUrl(array $urls, string $langId, string $languages_csv = ''): string
	{

		if (! empty($urls[$langId])) {
			return $urls[$langId];
		}

		$fallbacks = array_values(array_filter(array_map('trim', explode(',', $languages_csv))));
		foreach (['de', 'fr', 'it', 'en'] as $language) {
			if (! in_array($language, $fallbacks, true)) {
				$fallbacks[] = $language;
			}
		}

		foreach ($fallbacks as $fallback) {
			if (! empty($urls[$fallback])) {
				return $urls[$fallback];
			}
		}

		if (! empty($urls)) {
			return (string) reset($urls);
		}

		return '';
	}



	/**
	 * Normalize map_options layer id lists to [{ layerId: ... }, ...] for swissParksMapConfig
	 */
	public static function mapLayerEntries(mixed $layers): array
	{

		if (! is_array($layers) || empty($layers)) {
			return [];
		}

		$entries = [];
		foreach ($layers as $layer) {
			if (is_string($layer) && $layer !== '') {
				$entries[] = ['layerId' => $layer];
			} else if (is_array($layer) && ! empty($layer['layerId']) && is_string($layer['layerId'])) {
				$entries[] = ['layerId' => $layer['layerId']];
			}
		}

		return $entries;
	}



	/**
	 * Convert latitude and longitued values to CH1903 format
	 */
	public static function convertLatLonToCh1903(string $latitude, string $longitude): array {

		$return = [];

		$return['x'] = intval(self::wgs84ToCh1903X($latitude, $longitude) + 1000000);
		$return['y'] = intval(self::wgs84ToCh1903Y($latitude, $longitude) + 2000000);

		return $return;
	}


	/**
	 * Convert WGS lat/long (° dec) to CH y
	 */
	public static function wgs84ToCh1903Y(float $lat, float $long): float {

		// Converts degrees dec to sex
		$lat = self::decimalToSexagesimal($lat);
		$long = self::decimalToSexagesimal($long);

		// Converts degrees to seconds (sex)
		$lat = self::degreesToSeconds($lat);
		$long = self::degreesToSeconds($long);

		// Axiliary values (% Bern)
		$lat_aux = ($lat - 169028.66)/10000;
		$long_aux = ($long - 26782.5)/10000;

		// Process Y
		$y = 600072.37
			 + 211455.93 * $long_aux
			 -	10938.51 * $long_aux * $lat_aux
			 -			0.36 * $long_aux * pow($lat_aux,2)
			 -		 44.54 * pow($long_aux,3);

		return $y;
	}


	/**
	 * Convert WGS lat/long (° dec) to CH x
	 */
	public static function wgs84ToCh1903X(float $lat, float $long): float {

		// Converts degrees dec to sex
		$lat = self::decimalToSexagesimal($lat);
		$long = self::decimalToSexagesimal($long);

		// Converts degrees to seconds (sex)
		$lat = self::degreesToSeconds($lat);
		$long = self::degreesToSeconds($long);

		// Axiliary values (% Bern)
		$lat_aux = ($lat - 169028.66)/10000;
		$long_aux = ($long - 26782.5)/10000;

		// Process X
		$x = 200147.07
			 + 308807.95 * $lat_aux
			 +	 3745.25 * pow($long_aux,2)
			 +		 76.63 * pow($lat_aux,2)
			 -		194.56 * pow($long_aux,2) * $lat_aux
			 +		119.79 * pow($lat_aux,3);

		return $x;

	}



	/**
	 * Convert CH y/x to WGS lat
	 */
	public static function ch1903ToWgs84Lat(mixed $y, mixed $x): float {

		// Converts militar to civil and	to unit = 1000km
		// Axiliary values (% Bern)
		$y_aux = ($y - 600000)/1000000;
		$x_aux = ($x - 200000)/1000000;

		// Process lat
		$lat = 16.9023892
				 +	3.238272 * $x_aux
				 -	0.270978 * pow($y_aux,2)
				 -	0.002528 * pow($x_aux,2)
				 -	0.0447	 * pow($y_aux,2) * $x_aux
				 -	0.0140	 * pow($x_aux,3);

		// Unit 10000" to 1 " and converts seconds to degrees (dec)
		$lat = $lat * 100/36;

		return $lat;

	}


	/**
	 * Convert CH y/x to WGS long
	 */
	public static function ch1903ToWgs84Lng(mixed $y, mixed $x): float {

		// Converts militar to civil and	to unit = 1000km
		// Axiliary values (% Bern)
		$y_aux = ($y - 600000)/1000000;
		$x_aux = ($x - 200000)/1000000;

		// Process long
		$long = 2.6779094
					+ 4.728982 * $y_aux
					+ 0.791484 * $y_aux * $x_aux
					+ 0.1306	 * $y_aux * pow($x_aux,2)
					- 0.0436	 * pow($y_aux,3);

		// Unit 10000" to 1 " and converts seconds to degrees (dec)
		$long = $long * 100/36;

		return $long;

	}


	/**
	 * Convert SEX DMS angle to DEC
	 */
	public static function sexagesimalToDecimal(mixed $angle): float {

		// Extract DMS
		$deg = intval( $angle );
		$min = intval( ($angle-$deg)*100 );
		$sec = ((($angle-$deg)*100) - $min) * 100;

		// Result in degrees sex (dd.mmss)
		return $deg + ($sec/60 + $min)/60;

	}


	/**
	 * Convert DEC angle to SEX DMS
	 */
	public static function decimalToSexagesimal(float $angle): float {

		// Extract DMS
		$deg = intval( $angle );
		$min = intval( ($angle-$deg)*60 );
		$sec =	((($angle-$deg)*60)-$min)*60;

		// Result in degrees sex (dd.mmss)
		return $deg + $min/100 + $sec/10000;

	}


	/**
	 * Convert Degrees angle to seconds
	 */
	public static function degreesToSeconds(float $angle): float {

		// Extract DMS
		$deg = intval( $angle );
		$min = intval( ($angle-$deg)*100 );
		$sec = ((($angle-$deg)*100) - $min) * 100;

		// Result in degrees sex (dd.mmss)
		return $sec + $min*60 + $deg*3600;

	}

}
