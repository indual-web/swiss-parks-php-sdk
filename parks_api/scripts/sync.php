<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Sync offer data from XML export
|
*/


// Include API
require("../autoload.php");

try {

	// Initialize API and update local database from XML export
	$api = ParksAPI::forScript();
	exit($api->update() ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);

}
