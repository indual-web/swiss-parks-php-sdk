<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Sync offer data from XML export
|
| Usage:
|   php bin/sync.php           Incremental (?since= when last_import is set)
|   php bin/sync.php --force   Full import (no ?since=)
|
*/
require __DIR__ . '/../autoload.php';

try {

	$force = in_array('--force', $argv ?? [], true) || in_array('force', $argv ?? [], true);

	$api = ParksAPI::forScript();
	exit($api->update($force) ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);

}
