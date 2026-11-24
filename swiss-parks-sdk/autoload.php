<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
| Autoloader for ZIP installations. Composer projects use vendor/autoload.php.
|
*/


/*
|--------------------------------------------------------------------------
| SDK classes (PSR-4: SwissParks\Sdk\ => classes/)
|--------------------------------------------------------------------------
|
*/
spl_autoload_register(static function (string $class): void {

	$prefix = 'SwissParks\\Sdk\\';
	if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
		return;
	}

	$file = __DIR__ . '/classes/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
	if (is_file($file)) {
		require $file;
	}
});


/*
|--------------------------------------------------------------------------
| Include custom views
|--------------------------------------------------------------------------
|
*/
foreach (glob(__DIR__ . '/custom/*.php') ?: [] as $filePath) {
	require_once $filePath;
}
