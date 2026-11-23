<?php

declare(strict_types=1);

final class TestSdkFactory
{


	/**
	 * Build a ParksSDK with language only, isolated from config.local.php and the real database
	 */
	public static function create(array $configOverrides = [], string $language = 'de'): ParksSDK
	{

		$sdkPath = dirname(__DIR__) . '/swiss-parks-sdk';

		// Shipped defaults only, so local integrator settings cannot change test results
		$config = [];
		require $sdkPath . '/config/config.php';

		// Skip the constructor: it bootstraps the configured database and renders views
		$sdk = (new ReflectionClass(ParksSDK::class))->newInstanceWithoutConstructor();
		$sdk->config = array_merge($config, ['absolute_path' => $sdkPath], $configOverrides);
		$sdk->lang = new ParksLanguage($language, $sdk);
		$sdk->langId = $sdk->lang->langId;

		return $sdk;
	}
}
