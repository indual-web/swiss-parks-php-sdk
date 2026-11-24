<?php

declare(strict_types=1);

namespace SwissParks\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SwissParks\Sdk\Category;
use SwissParks\Sdk\Import;
use SwissParks\Sdk\Log;
use SwissParks\Sdk\Model;
use SwissParks\Sdk\SQLite;
use SwissParks\Sdk\SwissParksSdk;

final class ImportTest extends TestCase
{


	private const FIXTURES = __DIR__ . '/fixtures';

	private string $tmpDir;

	private SwissParksSdk $sdk;



	/**
	 * Wire an SDK against a temporary SQLite file and local fixture exports
	 */
	protected function setUp(): void
	{

		$this->tmpDir = sys_get_temp_dir() . '/parks-sdk-test-' . bin2hex(random_bytes(4));
		mkdir($this->tmpDir);

		// Taxonomy exports are read with file_get_contents, so local fixture paths work
		$this->sdk = TestSdkFactory::create([
			'db_path' => $this->tmpDir . '/offers.sqlite',
			'json_export_target_groups' => self::FIXTURES . '/target_groups.json',
			'json_export_categories' => self::FIXTURES . '/categories.json',
			'json_export_accessibilities' => self::FIXTURES . '/accessibilities.json',
			'json_export_fields_of_activity' => self::FIXTURES . '/fields_of_activity.json',
		]);
		$this->sdk->logger = new Log($this->sdk);
		(new ReflectionProperty(Log::class, 'logfile'))->setValue($this->sdk->logger, $this->tmpDir . '/import.log');
		$this->sdk->db = new SQLite($this->sdk);
		$this->sdk->model = new Model($this->sdk);
		$this->sdk->import = new Import($this->sdk);
	}



	/**
	 * Remove the temporary database, log and generated XML files
	 */
	protected function tearDown(): void
	{

		foreach (array_diff(scandir($this->tmpDir) ?: [], ['.', '..']) as $file) {
			unlink($this->tmpDir . '/' . $file);
		}
		rmdir($this->tmpDir);
	}



	/**
	 * Active offers are imported, inactive ones are skipped
	 */
	public function testImportStoresActiveOffersOnly(): void
	{

		$this->assertTrue($this->importFixture());
		$this->assertSame(2, $this->sdk->import->lastImportedOffers);
		$this->assertTrue($this->sdk->model->offerExists(101));
		$this->assertTrue($this->sdk->model->offerExists(102));
		$this->assertFalse($this->sdk->model->offerExists(103));
	}



	/**
	 * Imported event comes back through Model with title, dates, relations and contacts
	 */
	public function testImportedEventIsReadableThroughModel(): void
	{

		$this->importFixture();
		$offer = $this->sdk->model->getOffer(101);

		$this->assertIsObject($offer);
		$this->assertSame('Waldfest', $offer->title);
		$this->assertSame(Category::EVENT, $offer->root_category);
		$this->assertSame([1 => 'Familien'], $offer->targetGroups);
		$this->assertCount(1, $offer->dates);
		$this->assertSame('01.06.2099 10:00', $offer->dates[0]->date_from);
		$this->assertSame('01.06.2099 16:00', $offer->dates[0]->date_to);
		$this->assertCount(1, $offer->images);
		$this->assertSame("Anna Muster\nanna@example.org", $offer->contact);
		$this->assertSame("Testpark AG\n3000 Bern", $offer->institution);
	}



	/**
	 * Subcategory resolves to the activity root category and activity details are stored
	 */
	public function testImportedActivityResolvesRootCategoryAndDetails(): void
	{

		$this->importFixture();
		$offer = $this->sdk->model->getOffer(102);

		$this->assertIsObject($offer);
		$this->assertSame(Category::ACTIVITY, $offer->root_category);
		$this->assertEquals(135, $offer->time_required_minutes);
		$this->assertEquals(2, $offer->level_technics);
		$this->assertEquals(1, $offer->level_condition);
		$this->assertEquals(12.5, $offer->route_length);
		$this->assertSame('6,7', $offer->season_months);
	}



	/**
	 * Full-text index is rebuilt after import so search finds the offer
	 */
	public function testSearchFindsImportedOffer(): void
	{

		$this->importFixture();
		$result = $this->sdk->model->filterOffers(['search' => 'Waldfest']);

		$this->assertIsArray($result);
		$this->assertSame([101], array_map(fn($offer) => (int) $offer->offer_id, $result['data']));
	}



	/**
	 * Quotes in keyword filters are escaped, so they cannot widen the query
	 */
	public function testKeywordFiltersEscapeQuotes(): void
	{

		$this->importFixture();
		$injection = "x'OR'1'LIKE'1";

		$this->assertFalse($this->sdk->model->filterOffers(['keywords' => $injection]));
		$this->assertSame([1, 4], array_keys($this->sdk->model->getAllCategories(['keywords' => $injection])));
	}



	/**
	 * SQL fragments in ID, accessibility and park name filters neither break nor widen the query
	 */
	public function testFiltersIgnoreSqlFragments(): void
	{

		$this->importFixture();
		$payload = '0) OR (1=1';
		$filters = [
			'categories' => [[$payload], $payload],
			'level_technics' => [[$payload], $payload],
			'level_condition' => [[$payload], $payload],
			'project_status' => [[$payload], $payload],
			'municipalities' => [[$payload], $payload],
			'park_id' => [[$payload], $payload],
			'target_groups' => [[$payload]],
			'fields_of_activity' => [[$payload]],
			'accessibilities' => [["x' OR '1'='1"]],
			'user' => ["x' OR '1'='1"],
		];

		foreach ($filters as $field => $values) {
			foreach ($values as $value) {
				$this->assertFalse($this->sdk->model->filterOffers([$field => $value]), $field);
				$this->assertSame('', $this->sdk->db->getLastError(), $field);
			}
		}
	}



	/**
	 * Excluded park IDs match the park_id column
	 */
	public function testExcludeParkIdsFiltersByParkId(): void
	{

		$this->importFixture();

		$this->assertFalse($this->sdk->model->filterOffers(['exclude_park_ids' => [5]]));
		$this->assertCount(2, $this->sdk->model->filterOffers(['exclude_park_ids' => [99]])['data']);
	}



	/**
	 * Offer settings accept plain column names only and escape their values
	 */
	public function testOfferSettingsRejectUnsafeColumnsAndEscapeValues(): void
	{

		$this->importFixture();

		$this->assertCount(2, $this->sdk->model->filterOffers(['offer_settings' => ['park_id = 0 OR 1' => '1']])['data']);
		$this->assertFalse($this->sdk->model->filterOffers(['offer_settings' => ['park' => "x' OR '1'='1"]]));
		$this->assertSame('', $this->sdk->db->getLastError());
		$this->assertCount(2, $this->sdk->model->filterOffers(['offer_settings' => ['park' => 'Testpark']])['data']);
	}



	/**
	 * Unparsable dates are ignored instead of throwing
	 */
	public function testInvalidDateFilterIsIgnored(): void
	{

		$this->importFixture();

		$this->assertEquals($this->sdk->model->filterOffers([]), $this->sdk->model->filterOffers(['date_from' => 'no date', 'date_to' => '31.02.2099<x>']));
	}



	/**
	 * Forced re-import deletes offers that are no longer in the export
	 */
	public function testForcedReimportDeletesMissingOffers(): void
	{

		$this->importFixture();

		// Same export without offer 101
		$xml = simplexml_load_file(self::FIXTURES . '/offers.xml');
		unset($xml->Offer[0]);
		$xml->asXML($this->tmpDir . '/offers-update.xml');

		$this->assertTrue($this->sdk->import->import('file://' . $this->tmpDir . '/offers-update.xml', true));
		$this->assertSame(1, $this->sdk->import->lastDeletedOffers);
		$this->assertFalse($this->sdk->model->offerExists(101));
		$this->assertTrue($this->sdk->model->offerExists(102));
	}



	/**
	 * Offer with a category missing from the taxonomy is imported without hanging
	 */
	public function testImportKeepsOfferWithUnknownCategory(): void
	{

		$xml = simplexml_load_file(self::FIXTURES . '/offers.xml');
		$xml->Offer[1]->Categories->Category['identifier'] = '999';
		$xml->asXML($this->tmpDir . '/offers-unknown-category.xml');

		$this->assertTrue($this->sdk->import->import('file://' . $this->tmpDir . '/offers-unknown-category.xml', true));
		$this->assertTrue($this->sdk->model->offerExists(102));
	}



	/**
	 * Unreachable export makes the import fail
	 */
	public function testImportFailsForMissingXml(): void
	{

		$this->assertFalse($this->sdk->import->import('file://' . $this->tmpDir . '/missing.xml', true));
	}



	/**
	 * Import the offer fixture and refresh the model caches like SwissParksSdk::setup() does
	 */
	private function importFixture(): bool
	{

		$result = $this->sdk->import->import('file://' . self::FIXTURES . '/offers.xml', true);
		$this->sdk->model->reloadTargetGroups();
		$this->sdk->model->reloadFieldsOfActivity();
		$this->sdk->model->reloadCategories();

		return $result;
	}
}
