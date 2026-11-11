#!/bin/bash
#
# Drop SQLite DB, recreate schema, run full import.
#

cd "$(dirname "$0")" && exec php migrate.php
