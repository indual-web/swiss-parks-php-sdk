<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/


class ParksMap
{


	/**
	 * Build category → offer-id map fragment for swissParksMapConfig.offersData
	 */
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

		// Prefer the park's configured language order, then the default CH order
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

		// Last resort: any available URL rather than an empty string
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


}
