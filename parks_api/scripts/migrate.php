<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
*/


// Include API
require("../autoload.php");

// Update offer data
$api = new ParksAPI();
$api->migrate();
