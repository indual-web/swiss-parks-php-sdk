<?php

declare(strict_types=1);

$vendor = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendor)) {
	require $vendor;
} else {
	require dirname(__DIR__) . '/swiss-parks-sdk/autoload.php';
}
