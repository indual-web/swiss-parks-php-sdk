<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ParksFavoritesTest extends TestCase
{


	public function testParsesJsonObjectOfOfferIds(): void
	{

		$raw = json_encode([12 => 12, 34 => 34]);
		$this->assertSame([12 => 12, 34 => 34], ParksFavorites::parseCookie($raw));
	}



	public function testParsesJsonListOfOfferIds(): void
	{

		$raw = json_encode([12, 34]);
		$this->assertSame([12 => 12, 34 => 34], ParksFavorites::parseCookie($raw));
	}



	public function testInvalidPayloadReturnsEmptyList(): void
	{

		$this->assertSame([], ParksFavorites::parseCookie('not-json'));
		$this->assertSame([], ParksFavorites::parseCookie(''));
		$this->assertSame([], ParksFavorites::parseCookie('null'));
		$this->assertSame([], ParksFavorites::parseCookie('"string"'));
	}



	public function testIgnoresNonPositiveIds(): void
	{

		$raw = json_encode([0, -5, 7, 'x']);
		$this->assertSame([7 => 7], ParksFavorites::parseCookie($raw));
	}



	public function testEncodeDecodeRoundtrip(): void
	{

		$favorites = [12 => 12, 99 => 99];
		$encoded = ParksFavorites::encodeCookie($favorites);
		$this->assertSame($favorites, ParksFavorites::parseCookie($encoded));
		$this->assertJson($encoded);
	}



	public function testCookieOptionsUseLaxAndOptionalSecure(): void
	{

		$options = ParksFavorites::cookieOptions(false, true);
		$this->assertSame('/', $options['path']);
		$this->assertSame('Lax', $options['samesite']);
		$this->assertTrue($options['secure']);
		$this->assertGreaterThan(time(), $options['expires']);

		$cleared = ParksFavorites::cookieOptions(true, false);
		$this->assertFalse($cleared['secure']);
		$this->assertLessThanOrEqual(time(), $cleared['expires']);
	}



	public function testRequestIsSecureDetection(): void
	{

		$this->assertTrue(ParksFavorites::requestIsSecure(['HTTPS' => 'on']));
		$this->assertTrue(ParksFavorites::requestIsSecure(['SERVER_PORT' => '443']));
		$this->assertFalse(ParksFavorites::requestIsSecure(['HTTPS' => 'off', 'SERVER_PORT' => '80']));
	}
}
