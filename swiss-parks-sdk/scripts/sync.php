<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Sync offer data from XML export
|
*/


// Include SDK
require __DIR__ . '/../autoload.php';

try {

	// Initialize SDK and update local database from XML export
	$api = ParksAPI::forScript();
	exit($api->update() ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);

}
