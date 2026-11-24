<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
| Sync offer data from XML export
|
| Usage:
|   php bin/sync.php                  Incremental (?since= when last_import is set)
|   php bin/sync.php --force          Full import (no ?since=)
|   php bin/sync.php --config=<path>  Use a project config file (Composer)
|
*/
use SwissParks\Sdk\SwissParksSdk;

require_once __DIR__ . '/../autoload.php';

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'Forbidden: use CLI (bin/sync.sh) or public/sync.php with sync_secret.' . PHP_EOL;
	exit(1);
}

try {

	SwissParksSdk::useConfigFromArgv($argv ?? []);

	$force = in_array('--force', $argv ?? [], true) || in_array('force', $argv ?? [], true);

	$sdk = SwissParksSdk::forScript();
	exit($sdk->update($force) ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);

}
