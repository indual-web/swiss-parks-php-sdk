<?php

declare(strict_types=1);

namespace SwissParks\Sdk\Tests;

use ReflectionClass;
use SwissParks\Sdk\Language;
use SwissParks\Sdk\SwissParksSdk;

final class TestSdkFactory
{


	/**
	 * Build a SwissParksSdk with language only, isolated from config.local.php and the real database
	 */
	public static function create(array $configOverrides = [], string $language = 'de', string $sdkClass = SwissParksSdk::class): SwissParksSdk
	{

		$sdkPath = dirname(__DIR__) . '/swiss-parks-sdk';

		// Shipped defaults only, so local integrator settings cannot change test results
		$config = require $sdkPath . '/config/config.php';

		// Skip the constructor: it bootstraps the configured database and renders views
		$sdk = (new ReflectionClass($sdkClass))->newInstanceWithoutConstructor();
		$sdk->config = array_merge($config, ['absolute_path' => $sdkPath], $configOverrides);
		$sdk->lang = new Language($language, $sdk);
		$sdk->langId = $sdk->lang->langId;

		return $sdk;
	}
}
