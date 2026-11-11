<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Rebuild database and run full import
|
*/
require __DIR__ . '/../autoload.php';

try {

	// Rebuild database and run full import (no page rendering bootstrap)
	$api = ParksAPI::forMigration();
	exit($api->migrate() ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);

}
