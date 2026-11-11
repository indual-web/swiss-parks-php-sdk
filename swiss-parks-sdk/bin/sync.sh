#!/bin/bash
#
# Sync offer data from the XML export.
#
# Usage:
#   bash bin/sync.sh           Incremental (?since= when last_import is set)
#   bash bin/sync.sh --force   Full import (no ?since=)
#
# Rebuild schema + full import: bash bin/migrate.sh
#

set -euo pipefail

cd "$(dirname "$0")"

if [[ "${1:-}" == "--force" || "${1:-}" == "force" ]]; then
	exec php sync.php --force
fi

exec php sync.php
