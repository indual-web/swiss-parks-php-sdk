<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Autoload API
|
*/


/*
|--------------------------------------------------------------------------
| Start session
|--------------------------------------------------------------------------
|
*/
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}


/*
|--------------------------------------------------------------------------
| API version
|--------------------------------------------------------------------------
|
*/
define('API_VERSION', 22);


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
require_once('config.php');


/*
|--------------------------------------------------------------------------
| Include helper functions
|--------------------------------------------------------------------------
|
*/
foreach (glob(__DIR__.'/helpers/*.php') as $file_path) {
	require_once($file_path);
}


/*
|--------------------------------------------------------------------------
| Include main API classes
|--------------------------------------------------------------------------
|
*/
foreach (glob(__DIR__.'/classes/*.php') as $file_path) {
	require_once($file_path);
}


/*
|--------------------------------------------------------------------------
| Include custom views
|--------------------------------------------------------------------------
|
*/
foreach (glob(__DIR__.'/custom/*.php') as $file_path) {
	require_once($file_path);
}