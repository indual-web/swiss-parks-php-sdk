<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Add or remove offer from favorites
|
*/


// Include API
require("../autoload.php");
$api = new ParksAPI();

// Get offer id
$offer_id = 0;
if (isset($_GET['offer_id']) && ($_GET['offer_id'] > 0)) {
	$offer_id = intval($_GET['offer_id']);
}

// Action: add or remove favorite
if ($offer_id > 0) {

	// Initialize API and update local database from XML export
	echo $api->toggle_favorite($offer_id);

}

// Action: remove all favorites
if (isset($_GET['action']) && ($_GET['action'] == 'clean')) {
	$api->clean_favorites();
}