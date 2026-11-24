<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/

namespace SwissParks\Sdk;


class Map
{


	/**
	 * Build category → offer-id map fragment for swissParksMapConfig.offersData
	 */
	public static function formatOffersByCategoryJs(array $offers, bool $ignoreCategoriesWithSubcategories = false): string {

		if (empty($offers)) {
			return '';
		}

		$categoriesOffers = [];

		// Populate categories with subcategories
		$categoriesWithSubcategories = [
			Category::EVENT,
			Category::PRODUCT,
				Category::INFORMATION,
				Category::SIGHT,
				Category::REGIONAL_PRODUCT,
				Category::GASTRONOMY_AND_ACCOMMODATION,
					Category::GASTRONOMY,
					Category::ACCOMMODATION,
				Category::INFRASTRUCTURE,
			Category::BOOKING,
			Category::ACTIVITY,
				Category::SUMMER_ACTIVITIES,
				Category::WINTER_ACTIVITIES,
			Category::PROJECT,
			Category::RESEARCH,
		];

		// Populate offers by categories
		foreach ($offers as $offer) {

			if (! empty($offer->categories) && is_array($offer->categories)) {
				foreach ($offer->categories as $categoryId => $category) {

					// Skip categories with subcategories
					if ($ignoreCategoriesWithSubcategories && in_array($categoryId, $categoriesWithSubcategories)) {
						continue;
					}

					// Add offer to category
					$categoriesOffers[$categoryId][] = $offer->offer_id;

				}
			}
		}

		$categoriesOffersView = '';

		if (! empty($categoriesOffers)) {
			foreach ($categoriesOffers as $categoryId => $offerIds) {
				$categoriesOffersView .= $categoryId . ': [\'' . implode('\', \'', $offerIds) . '\'],';
			}
		}

		return $categoriesOffersView;
	}



	/**
	 * Resolve a language-specific map layer URL (CSV languages, then de → fr → it → en)
	 */
	public static function resolveI18nUrl(array $urls, string $langId, string $languagesCsv = ''): string
	{

		if (! empty($urls[$langId])) {
			return $urls[$langId];
		}

		// Prefer the park's configured language order, then the default CH order
		$fallbacks = array_values(array_filter(array_map('trim', explode(',', $languagesCsv))));
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
