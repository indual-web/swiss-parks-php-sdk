<?php

declare(strict_types=1);

namespace SwissParks\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use SwissParks\Sdk\Favorites;

final class FavoritesTest extends TestCase
{


	/**
	 * JSON object of offer IDs is parsed into an ID map
	 */
	public function testParsesJsonObjectOfOfferIds(): void
	{

		$raw = json_encode([12 => 12, 34 => 34]);
		$this->assertSame([12 => 12, 34 => 34], Favorites::parseCookie($raw));
	}



	/**
	 * JSON list of offer IDs is parsed into an ID map
	 */
	public function testParsesJsonListOfOfferIds(): void
	{

		$raw = json_encode([12, 34]);
		$this->assertSame([12 => 12, 34 => 34], Favorites::parseCookie($raw));
	}



	/**
	 * Invalid, empty or non-array payloads give no favorites
	 */
	public function testInvalidPayloadReturnsEmptyList(): void
	{

		$this->assertSame([], Favorites::parseCookie('not-json'));
		$this->assertSame([], Favorites::parseCookie(''));
		$this->assertSame([], Favorites::parseCookie('null'));
		$this->assertSame([], Favorites::parseCookie('"string"'));
	}



	/**
	 * Zero, negative and non-numeric IDs are dropped
	 */
	public function testIgnoresNonPositiveIds(): void
	{

		$raw = json_encode([0, -5, 7, 'x']);
		$this->assertSame([7 => 7], Favorites::parseCookie($raw));
	}



	/**
	 * Encoded cookie value is JSON and parses back to the same favorites
	 */
	public function testEncodeDecodeRoundtrip(): void
	{

		$favorites = [12 => 12, 99 => 99];
		$encoded = Favorites::encodeCookie($favorites);
		$this->assertSame($favorites, Favorites::parseCookie($encoded));
		$this->assertJson($encoded);
	}



	/**
	 * Cookie uses path /, SameSite=Lax, Secure on HTTPS and expires in the past when cleared
	 */
	public function testCookieOptionsUseLaxAndOptionalSecure(): void
	{

		$options = Favorites::cookieOptions(false, true);
		$this->assertSame('/', $options['path']);
		$this->assertSame('Lax', $options['samesite']);
		$this->assertTrue($options['secure']);
		$this->assertGreaterThan(time(), $options['expires']);

		$cleared = Favorites::cookieOptions(true, false);
		$this->assertFalse($cleared['secure']);
		$this->assertLessThanOrEqual(time(), $cleared['expires']);
	}



	/**
	 * HTTPS is detected from the HTTPS flag or port 443
	 */
	public function testRequestIsSecureDetection(): void
	{

		$this->assertTrue(Favorites::requestIsSecure(['HTTPS' => 'on']));
		$this->assertTrue(Favorites::requestIsSecure(['SERVER_PORT' => '443']));
		$this->assertFalse(Favorites::requestIsSecure(['HTTPS' => 'off', 'SERVER_PORT' => '80']));
	}
}
