<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
*/


// Include API
require("../autoload.php");

// Rebuild database and run full import (no page rendering bootstrap)
$api = ParksAPI::forMigration();
$api->migrate();
