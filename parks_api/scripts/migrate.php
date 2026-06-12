<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
*/


// Include API
require("../autoload.php");

try {

	// Rebuild database and run full import (no page rendering bootstrap)
	$api = ParksAPI::forMigration();
	exit($api->migrate() ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);

}
