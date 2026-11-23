<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParksFilterSanitizeTest extends TestCase
{


	private ParksSDK $sdk;

	private ReflectionMethod $sanitize;



	/**
	 * SDK with shipped config and the route_times labels set by loadSystemConfig()
	 */
	protected function setUp(): void
	{

		$this->sdk = TestSdkFactory::create(['use_sessions' => true]);
		$this->sdk->config['route_times'] = ['&lt; 2h', '2 - 4h', '4 - 6h', '&gt; 6h'];
		$this->sdk->sessionName = 'parks_test';
		$this->sanitize = new ReflectionMethod(ParksSDK::class, 'sanitizeFilterValue');
	}



	/**
	 * Reset globals touched by initFilter()
	 */
	protected function tearDown(): void
	{

		$_SESSION = [];
		$_POST = [];
		$_GET = [];
	}



	/**
	 * ID filters keep positive integers only and never leave an SQL fragment
	 */
	public function testIdFiltersKeepPositiveIntegers(): void
	{

		$this->assertSame([3, 4], $this->sanitize->invoke($this->sdk, 'categories', ['3', '0', '-1', 'abc', '4) OR (1=1']));
		$this->assertSame([5], $this->sanitize->invoke($this->sdk, 'park_id', '5'));
		$this->assertNull($this->sanitize->invoke($this->sdk, 'level_technics', ['abc', ['1']]));
	}



	/**
	 * Non-ID fields are validated by type, format or allowlist
	 */
	public function testOtherFieldsAreValidated(): void
	{

		$this->assertSame(['http://example.org/icon.svg', '1'], $this->sanitize->invoke($this->sdk, 'accessibilities', ['http://example.org/icon.svg', '1', '', ['x']]));
		$this->assertSame(12, $this->sanitize->invoke($this->sdk, 'route_length_min', '12) OR (1=1'));
		$this->assertSame('01.06.2099', $this->sanitize->invoke($this->sdk, 'date_from', '01.06.2099'));
		$this->assertNull($this->sanitize->invoke($this->sdk, 'date_to', "2099-06-01' OR '1'='1"));
		$this->assertSame(['< 2h', '> 6h'], $this->sanitize->invoke($this->sdk, 'time_required', ['< 2h', "x' OR '1'='1", '> 6h']));
		$this->assertSame(200, mb_strlen($this->sanitize->invoke($this->sdk, 'search', str_repeat('ä', 300))));
		$this->assertNull($this->sanitize->invoke($this->sdk, 'search', ['x']));
		$this->assertNull($this->sanitize->invoke($this->sdk, 'unknown', '1'));
	}



	/**
	 * Poisoned session values from earlier requests are cleaned on the next initFilter()
	 */
	public function testInitFilterCleansSessionValues(): void
	{

		$_SESSION['parks_test']['filter'] = [
			'level_technics' => ['1) OR (1=1', 'x'],
			'date_from' => "x' OR '1'='1",
			'search' => 'Wald',
		];

		(new ReflectionMethod(ParksSDK::class, 'initFilter'))->invoke($this->sdk);

		$this->assertSame([1], $this->sdk->filterData['level_technics']);
		$this->assertNull($this->sdk->filterData['date_from']);
		$this->assertSame('Wald', $this->sdk->filterData['search']);
		$this->assertSame($this->sdk->filterData, $_SESSION['parks_test']['filter']);
	}
}
