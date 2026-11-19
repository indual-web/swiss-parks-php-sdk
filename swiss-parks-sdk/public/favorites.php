<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/


// Include SDK
require __DIR__ . '/../autoload.php';
$sdk = new ParksSDK();

// Get offer id
$offer_id = 0;
if (isset($_GET['offer_id']) && ($_GET['offer_id'] > 0)) {
	$offer_id = intval($_GET['offer_id']);
}

// Action: add or remove favorite
if ($offer_id > 0) {

	// Initialize SDK and update local database from XML export
	echo $sdk->toggleFavorite($offer_id);

}

// Action: remove all favorites
if (isset($_GET['action']) && ($_GET['action'] == 'clean')) {
	$sdk->cleanFavorites();
}