<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Autoload SDK
|
*/


/*
|--------------------------------------------------------------------------
| SDK version
|--------------------------------------------------------------------------
|
*/
define('SDK_VERSION', 22);


/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
|
*/
define('CATEGORY_EVENT', 1);
define('CATEGORY_PRODUCT', 2);
define('CATEGORY_BOOKING', 3);
define('CATEGORY_ACTIVITY', 4);
define('CATEGORY_PROJECT', 1000);
define('CATEGORY_RESEARCH', 5000);
define('CATEGORY_GASTRONOMY_AND_ACCOMMODATION', 200);
define('CATEGORY_REGIONAL_PRODUCT', 19);
define('CATEGORY_GASTRONOMY', 20);
define('CATEGORY_ACCOMMODATION', 21);
define('CATEGORY_SIGHT', 22);
define('CATEGORY_SUMMER_ACTIVITIES', 50);
define('CATEGORY_WINTER_ACTIVITIES', 51);
define('CATEGORY_INFORMATION', 79);
define('CATEGORY_INFRASTRUCTURE', 100);


/*
|--------------------------------------------------------------------------
| Offer status
|--------------------------------------------------------------------------
|
*/
define('OFFER_STATUS_STUB', 0);
define('OFFER_STATUS_INACTIVE', 1);
define('OFFER_STATUS_ACTIVE', 2);


/*
|--------------------------------------------------------------------------
| Misc
|--------------------------------------------------------------------------
|
*/
define('GINTO_INFOS_AVAILABLE', 1);


/*
|--------------------------------------------------------------------------
| Include configuration
|--------------------------------------------------------------------------
|
*/
require_once __DIR__ . '/config/config.php';
if (file_exists(__DIR__ . '/config/config.local.php')) {
	require_once __DIR__ . '/config/config.local.php';
}


/*
|--------------------------------------------------------------------------
| Start session
|--------------------------------------------------------------------------
|
| Only when use_sessions is enabled and headers are still writable.
|
*/
if (
	! empty($config['use_sessions'])
	&& session_status() == PHP_SESSION_NONE
	&& ! headers_sent()
) {
	session_start();
}


/*
|--------------------------------------------------------------------------
| Include main SDK classes
|--------------------------------------------------------------------------
|
*/
$class_files = glob(__DIR__ . '/classes/*.php') ?: [];
usort($class_files, static function (string $a, string $b): int {
	$rank = static function (string $path): int {
		$base = basename($path);
		if ($base === 'ParksView.php') {
			return 0;
		}
		if (str_ends_with($base, 'View.php')) {
			return 2;
		}
		return 1;
	};
	return $rank($a) <=> $rank($b) ?: strcmp($a, $b);
});
foreach ($class_files as $file_path) {
	require_once $file_path;
}


/*
|--------------------------------------------------------------------------
| Include custom views
|--------------------------------------------------------------------------
|
*/
foreach (glob(__DIR__ . '/custom/*.php') as $file_path) {
	require_once $file_path;
}
