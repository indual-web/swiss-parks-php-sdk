<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/
require __DIR__ . '/../autoload.php';

if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'Forbidden: run this script from the CLI only (bin/migrate.sh).' . PHP_EOL;
	exit(1);
}

try {

	// Rebuild database and run full import (no page rendering bootstrap)
	$sdk = ParksSDK::forMigration();
	exit($sdk->migrate() ? 0 : 1);

} catch (Throwable $e) {

	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);

}
