<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/


class ParksFavorites
{


	/**
	 * Parse favorites cookie payload (JSON map/list of offer IDs)
	 */
	public static function parseCookie(string $raw): array
	{

		$decoded = json_decode($raw, true);
		if (! is_array($decoded)) {
			return [];
		}

		$favorites = [];
		foreach ($decoded as $value) {
			$id = (int) $value;
			if ($id > 0) {
				$favorites[$id] = $id;
			}
		}

		return $favorites;
	}



	/**
	 * Encode favorites for the cookie value
	 */
	public static function encodeCookie(array $favorites): string
	{

		$normalized = self::parseCookie(json_encode(array_values($favorites)) ?: '[]');

		return json_encode($normalized) ?: '{}';
	}



	/**
	 * Cookie options for setcookie()
	 */
	public static function cookieOptions(bool $empty, bool $secure = false): array
	{

		return [
			'expires' => $empty ? time() : time() + (60 * 60 * 24 * 365),
			'path' => '/',
			'secure' => $secure,
			'samesite' => 'Lax',
		];
	}



	/**
	 * Detect HTTPS for the Secure cookie flag
	 */
	public static function requestIsSecure(array $server = []): bool
	{

		if ($server === []) {
			$server = $_SERVER;
		}

		return (! empty($server['HTTPS']) && $server['HTTPS'] !== 'off')
			|| (isset($server['SERVER_PORT']) && (int) $server['SERVER_PORT'] === 443);
	}


}
