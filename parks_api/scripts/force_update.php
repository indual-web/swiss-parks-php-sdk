<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Update offer data (via cronjob)
|
*/


// Include API
require("../autoload.php");

try {

	// Initialize API and update local database from XML export
	$api = ParksAPI::forScript();
	exit($api->update(true) ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);

}
