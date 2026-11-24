<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
| Drop SQLite DB, recreate schema, run full import
|
| Usage:
|   php bin/migrate.php                       Always migrate
|   php bin/migrate.php --if-outdated         Only when the DB is missing or older than VERSION
|   php bin/migrate.php --config=<path>       Use a project config file (Composer)
|
*/
use SwissParks\Sdk\SwissParksSdk;

require_once __DIR__ . '/../autoload.php';

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'Forbidden: run this script from the CLI only (bin/migrate.sh).' . PHP_EOL;
	exit(1);
}

try {

	SwissParksSdk::useConfigFromArgv($argv ?? []);

	// Rebuild database and run full import (no page rendering bootstrap)
	$sdk = SwissParksSdk::forMigration();

	// Composer post-install/update hooks run this on every install; skip when nothing changed
	if (in_array('--if-outdated', $argv ?? [], true) && ! $sdk->isMigrationNeeded()) {
		echo 'migrate: skip (database is up to date, sdk_version=' . SwissParksSdk::VERSION . ')' . PHP_EOL;
		exit(0);
	}

	exit($sdk->migrate() ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, 'migrate: fail (' . $e->getMessage() . ')' . PHP_EOL);
	exit(1);

}
