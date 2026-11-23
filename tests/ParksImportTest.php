<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParksImportTest extends TestCase
{


	private const FIXTURES = __DIR__ . '/fixtures';

	private string $tmpDir;

	private ParksSDK $sdk;



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
		$this->sdk->logger = new ParksLog($this->sdk);
		(new ReflectionProperty(ParksLog::class, 'logfile'))->setValue($this->sdk->logger, $this->tmpDir . '/import.log');
		$this->sdk->db = new ParksSQLite($this->sdk);
		$this->sdk->model = new ParksModel($this->sdk);
		$this->sdk->import = new ParksImport($this->sdk);
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
	 * Imported event comes back through ParksModel with title, dates, relations and contacts
	 */
	public function testImportedEventIsReadableThroughModel(): void
	{

		$this->importFixture();
		$offer = $this->sdk->model->getOffer(101);

		$this->assertIsObject($offer);
		$this->assertSame('Waldfest', $offer->title);
		$this->assertSame(CATEGORY_EVENT, $offer->root_category);
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
		$this->assertSame(CATEGORY_ACTIVITY, $offer->root_category);
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
	 * Import the offer fixture and refresh the model caches like ParksSDK::setup() does
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
