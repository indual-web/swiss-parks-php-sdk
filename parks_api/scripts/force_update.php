<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Update offer data (via cronjob)
|
*/


// Include API
require("../autoload.php");

// Initialize API and update local database from XML export
$api = new ParksAPI();
$api->update(true);
