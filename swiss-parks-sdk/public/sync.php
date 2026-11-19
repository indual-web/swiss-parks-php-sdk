<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
| HTTP sync for shared hosting without SSH (URL cron).
| Requires config sync_secret. Prefer CLI: bash bin/sync.sh
|
| Usage:
|   GET /swiss-parks-sdk/public/sync.php?token=YOUR_SECRET
|   GET /swiss-parks-sdk/public/sync.php?token=YOUR_SECRET&force=1
|   Header: X-Sync-Token: YOUR_SECRET
|
*/
require __DIR__ . '/../autoload.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');

try {

	$sdk = ParksSDK::forScript();
	$secret = (string) ($sdk->config['sync_secret'] ?? '');

	// Empty secret disables the endpoint
	if ($secret === '') {
		http_response_code(403);
		echo 'sync: fail (HTTP sync disabled: set sync_secret in config)' . PHP_EOL;
		exit;
	}

	$token = '';
	if (! empty($_SERVER['HTTP_X_SYNC_TOKEN'])) {
		$token = (string) $_SERVER['HTTP_X_SYNC_TOKEN'];
	} elseif (isset($_GET['token'])) {
		$token = (string) $_GET['token'];
	}

	if ($token === '' || ! hash_equals($secret, $token)) {
		http_response_code(403);
		echo 'sync: fail (forbidden)' . PHP_EOL;
		exit;
	}

	// Match CLI import limits for long shared-hosting runs
	@set_time_limit(0);
	@ini_set('memory_limit', '512M');

	$force = isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true');

	ob_start();
	$ok = $sdk->update($force);
	$body = ob_get_clean();

	http_response_code($ok ? 200 : 500);
	echo $body !== '' ? $body : ('sync: ' . ($ok ? 'ok' : 'fail') . PHP_EOL);

} catch (Throwable $e) {

	http_response_code(500);
	echo 'sync: fail (' . $e->getMessage() . ')' . PHP_EOL;

}
