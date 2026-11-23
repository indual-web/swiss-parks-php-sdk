<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParksCanonicalUrlTest extends TestCase
{


	private CanonicalUrlTestSdk $sdk;



	/**
	 * SEO-enabled SDK with a real view and a model stub that knows offer 101 "Waldfest"
	 */
	protected function setUp(): void
	{

		$this->sdk = $this->createSdk();
	}



	/**
	 * Offer id comes from the title slug, POI id from the trailing POI segment
	 */
	public function testParseOfferDetailWithAndWithoutPoi(): void
	{

		$parse = new ReflectionMethod(ParksSDK::class, 'parseOfferDetailFromUrl');

		$this->assertSame(['offer_id' => 123, 'poi_id' => 0], $parse->invoke($this->sdk, '/angebote/offer-detail/berg-wanderung-123/'));
		$this->assertSame(['offer_id' => 123, 'poi_id' => 456], $parse->invoke($this->sdk, '/angebote/offer-detail/bergwanderung-123/poi/456'));
		$this->assertSame(['offer_id' => 0, 'poi_id' => 0], $parse->invoke($this->sdk, '/angebote/page/2'));
	}



	/**
	 * Paths are decoded, slash runs collapsed, query and trailing slash removed
	 */
	public function testNormalizeUrlPath(): void
	{

		$normalize = new ReflectionMethod(ParksSDK::class, 'normalizeUrlPath');

		$this->assertSame('/angebote/offer-detail/x-1', $normalize->invoke($this->sdk, '/angebote//offer-detail/x-1/?a=1&b=2'));
		$this->assertSame('/angebote', $normalize->invoke($this->sdk, 'https://example.com/angebote/'));
		$this->assertSame('/evil.com/x', $normalize->invoke($this->sdk, '/%2F%2Fevil.com/x'));
		$this->assertSame('/evil.com/x', $normalize->invoke($this->sdk, '///evil.com/x?a=1'));
		$this->assertSame('/über-uns', $normalize->invoke($this->sdk, '/%C3%BCber-uns'));
		$this->assertSame('', $normalize->invoke($this->sdk, '/'));
	}



	/**
	 * Canonical URL passes without redirect; query strings are ignored
	 */
	public function testCanonicalUrlIsAccepted(): void
	{

		$this->sdk->setScriptUrl('/angebote/offer-detail/waldfest-101/?utm_source=test');

		$this->assertTrue($this->sdk->enforceOfferDetailCanonicalUrl());
		$this->assertSame([], $this->sdk->headers);
	}



	/**
	 * Outdated slug, pagination segment and POI URLs redirect to the canonical path
	 */
	public function testNonCanonicalUrlsRedirect(): void
	{

		$this->assertRedirect('/angebote/offer-detail/alter-titel-101', '/angebote/offer-detail/waldfest-101');
		$this->assertRedirect('/angebote/page/3/offer-detail/waldfest-101', '/angebote/offer-detail/waldfest-101');
		$this->assertRedirect('/angebote/offer-detail/alt-101/poi/5', '/angebote/offer-detail/waldfest-101/poi/5');
	}



	/**
	 * Trusted page base URL replaces any junk prefix of the request
	 */
	public function testPageBaseUrlReplacesJunkPrefix(): void
	{

		$this->assertRedirect('/angebote/irgendwas/offer-detail/xyz-101', '/angebote/offer-detail/waldfest-101', 'https://example.com/angebote/');
	}



	/**
	 * Encoded or repeated slashes and backslashes never produce a protocol-relative Location
	 */
	public function testRedirectCannotLeaveTheHost(): void
	{

		$this->assertRedirect('/%2F%2Fevil.com/offer-detail/alt-101', '/evil.com/offer-detail/waldfest-101');
		$this->assertRedirect('///evil.com/offer-detail/alt-101', '/evil.com/offer-detail/waldfest-101');
		$this->assertRedirect('/%5Cevil.com/offer-detail/alt-101', '/%5Cevil.com/offer-detail/waldfest-101');
		$this->assertRedirect('/angebote/offer-detail/alt-101', '/x/offer-detail/waldfest-101', '//evil.com/x/');
	}



	/**
	 * Unknown offer exits with a plain 404 page by default
	 */
	public function testUnknownOfferSendsNotFound(): void
	{

		$this->sdk->setScriptUrl('/angebote/offer-detail/bergwanderung-999999');

		try {
			$this->sdk->enforceOfferDetailCanonicalUrl();
			$this->fail('Expected the request to terminate');
		} catch (CanonicalUrlTerminated $e) {
			$this->assertStringContainsString('404 Not Found', $e->getMessage());
		}
		$this->assertSame([['HTTP/1.0 404 Not Found', 404]], $this->sdk->headers);
	}



	/**
	 * With soft_errors the status is set and the CMS gets false to render its own 404 page
	 */
	public function testUnknownOfferWithSoftErrorsReturnsFalse(): void
	{

		$sdk = $this->createSdk(['soft_errors' => true]);
		$sdk->setScriptUrl('/angebote/offer-detail/bergwanderung-999999');

		$this->assertFalse($sdk->enforceOfferDetailCanonicalUrl());
		$this->assertSame([['HTTP/1.0 404 Not Found', 404]], $sdk->headers);
	}



	/**
	 * Without SEO URLs the method is a no-op
	 */
	public function testNoOpWithoutSeoUrls(): void
	{

		$sdk = $this->createSdk(['seo_urls' => false]);
		$sdk->setScriptUrl('/angebote/offer-detail/alter-titel-101');

		$this->assertTrue($sdk->enforceOfferDetailCanonicalUrl());
		$this->assertSame([], $sdk->headers);
	}



	/**
	 * Once output has started the redirect is skipped and rendering continues
	 */
	public function testNoRedirectAfterHeadersSent(): void
	{

		$this->sdk->headersSent = true;
		$this->sdk->setScriptUrl('/angebote/offer-detail/alter-titel-101');

		$this->assertTrue($this->sdk->enforceOfferDetailCanonicalUrl());
		$this->assertSame([], $this->sdk->headers);
	}



	/**
	 * Request the URL and expect a terminated 301 to the given Location
	 */
	private function assertRedirect(string $requestUrl, string $expectedLocation, string $pageBaseUrl = ''): void
	{

		$this->sdk->headers = [];
		$this->sdk->setScriptUrl($requestUrl);

		try {
			$this->sdk->enforceOfferDetailCanonicalUrl($pageBaseUrl);
			$this->fail('Expected a redirect for ' . $requestUrl);
		} catch (CanonicalUrlTerminated) {
			$this->assertSame([['Location: ' . $expectedLocation, 301]], $this->sdk->headers, $requestUrl);
		}
	}



	/**
	 * Build the test SDK without database access
	 */
	private function createSdk(array $configOverrides = []): CanonicalUrlTestSdk
	{

		$sdk = TestSdkFactory::create(array_merge(['seo_urls' => true], $configOverrides), 'de', CanonicalUrlTestSdk::class);
		$sdk->view = new ParksView($sdk);
		$sdk->model = (new ReflectionClass(CanonicalUrlTestModel::class))->newInstanceWithoutConstructor();

		return $sdk;
	}
}



/**
 * Records headers instead of sending them and throws instead of exiting
 */
final class CanonicalUrlTestSdk extends ParksSDK
{


	public array $headers = [];

	public bool $headersSent = false;



	protected function sendHeader(string $header, int $statusCode): bool
	{

		if ($this->headersSent) {
			return false;
		}

		$this->headers[] = [$header, $statusCode];

		return true;
	}



	protected function terminate(string $output = ''): never
	{
		throw new CanonicalUrlTerminated($output);
	}
}



/**
 * Model stub with a single known offer
 */
final class CanonicalUrlTestModel extends ParksModel
{


	public function getOffer(int|object $offer, bool $returnMinimal = false, bool $mapMode = false): object|false
	{
		return ($offer === 101) ? (object) ['offer_id' => 101, 'title' => 'Waldfest'] : false;
	}
}



final class CanonicalUrlTerminated extends RuntimeException
{
}
