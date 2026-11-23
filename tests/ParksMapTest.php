<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParksMapTest extends TestCase
{


	/**
	 * URL in the requested language wins
	 */
	public function testResolveI18nUrlPrefersRequestedLanguage(): void
	{

		$this->assertSame('fr-url', ParksMap::resolveI18nUrl(['de' => 'de-url', 'fr' => 'fr-url'], 'fr'));
	}



	/**
	 * Missing language falls back to the layer's configured language order
	 */
	public function testResolveI18nUrlUsesConfiguredLanguageOrder(): void
	{

		$urls = ['de' => 'de-url', 'it' => 'it-url'];
		$this->assertSame('it-url', ParksMap::resolveI18nUrl($urls, 'en', 'it, de'));
	}



	/**
	 * Without configured order the Swiss default order de, fr, it, en applies
	 */
	public function testResolveI18nUrlFallsBackToSwissDefaultOrder(): void
	{

		$this->assertSame('de-url', ParksMap::resolveI18nUrl(['fr' => 'fr-url', 'de' => 'de-url'], 'en'));
		$this->assertSame('fr-url', ParksMap::resolveI18nUrl(['de' => '', 'fr' => 'fr-url'], 'de'));
	}



	/**
	 * Any available URL is used as last resort; no URLs give an empty string
	 */
	public function testResolveI18nUrlLastResortAndEmpty(): void
	{

		$this->assertSame('rm-url', ParksMap::resolveI18nUrl(['rm' => 'rm-url'], 'de'));
		$this->assertSame('', ParksMap::resolveI18nUrl([], 'de'));
	}



	/**
	 * Layer ids and layerId arrays are normalised; invalid entries are dropped
	 */
	public function testMapLayerEntriesNormalizesIds(): void
	{

		$layers = ['base', ['layerId' => 'trails'], '', ['other' => 'x'], ['layerId' => 5], 7];
		$this->assertSame([['layerId' => 'base'], ['layerId' => 'trails']], ParksMap::mapLayerEntries($layers));
	}



	/**
	 * Non-array or empty input gives no layer entries
	 */
	public function testMapLayerEntriesRejectsNonArrays(): void
	{

		$this->assertSame([], ParksMap::mapLayerEntries('base'));
		$this->assertSame([], ParksMap::mapLayerEntries(null));
		$this->assertSame([], ParksMap::mapLayerEntries([]));
	}



	/**
	 * Offers are grouped by category for the map config, optionally without parent categories
	 */
	public function testFormatOffersByCategoryJs(): void
	{

		$offers = [
			(object) ['offer_id' => 10, 'categories' => [CATEGORY_EVENT => 'x', 30 => 'x']],
			(object) ['offer_id' => 11, 'categories' => [CATEGORY_EVENT => 'x']],
			(object) ['offer_id' => 12],
		];

		$this->assertSame("1: ['10', '11'],30: ['10'],", ParksMap::formatOffersByCategoryJs($offers));
		$this->assertSame("30: ['10'],", ParksMap::formatOffersByCategoryJs($offers, true));
		$this->assertSame('', ParksMap::formatOffersByCategoryJs([]));
	}
}
