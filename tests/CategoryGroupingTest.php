<?php

declare(strict_types=1);

namespace SwissParks\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SwissParks\Sdk\Model;
use SwissParks\Sdk\SQLite;
use SwissParks\Sdk\SwissParksSdk;

final class CategoryGroupingTest extends TestCase
{


	private string $tmpDir;

	private SwissParksSdk $sdk;



	/**
	 * Wire an SDK against an empty temporary SQLite file, as before the first import
	 */
	protected function setUp(): void
	{

		$this->tmpDir = sys_get_temp_dir() . '/parks-sdk-test-' . bin2hex(random_bytes(4));
		mkdir($this->tmpDir);

		$this->sdk = TestSdkFactory::create(['db_path' => $this->tmpDir . '/offers.sqlite']);
		$this->sdk->db = new SQLite($this->sdk);
		$this->sdk->model = new Model($this->sdk);
	}



	/**
	 * Remove the temporary database
	 */
	protected function tearDown(): void
	{

		foreach (array_diff(scandir($this->tmpDir) ?: [], ['.', '..']) as $file) {
			unlink($this->tmpDir . '/' . $file);
		}
		rmdir($this->tmpDir);
	}



	/**
	 * Filter categories on an empty database return an empty array, not false
	 */
	public function testGroupCategoriesByParentReturnsArrayWithoutCategories(): void
	{

		$method = new ReflectionMethod(SwissParksSdk::class, 'groupCategoriesByParent');

		$this->assertSame([], $method->invoke($this->sdk, false));
	}
}
