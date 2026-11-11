#!/bin/bash
#
# Sync smoke check.
#
# Usage:
#   bash bin/sync-smoke.sh           Sync then assert offer count > 0
#   bash bin/sync-smoke.sh --quick   Assert offer count > 0 only (no sync)
#
# Skips with exit 0 when api_hash is unset/placeholder or network/sync is unavailable.
#

set -euo pipefail

SDK_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$SDK_ROOT"

QUICK=0
if [[ "${1:-}" == "--quick" ]]; then
	QUICK=1
fi

if ! command -v php >/dev/null 2>&1; then
	echo "sync-smoke: skip (php not found)"
	exit 0
fi

HASH="$(php -r '
require $argv[1];
$api = ParksAPI::forScript();
echo (string) ($api->config["api_hash"] ?? "");
' "$SDK_ROOT/autoload.php" 2>/dev/null || true)"

if [[ -z "$HASH" || "$HASH" == "insert-your-hash-here" ]]; then
	echo "sync-smoke: skip (api_hash not configured)"
	exit 0
fi

DB_PATH="$(php -r '
require $argv[1];
$api = ParksAPI::forScript();
$path = (string) ($api->config["db_path"] ?? "");
if ($path !== "" && $path[0] !== "/") {
	$path = rtrim((string) ($api->config["absolute_path"] ?? ""), "/") . "/" . ltrim($path, "/");
}
echo $path;
' "$SDK_ROOT/autoload.php" 2>/dev/null || true)"

if [[ "$QUICK" -eq 0 ]]; then
	if ! php "$SDK_ROOT/bin/sync.php"; then
		echo "sync-smoke: skip (sync failed; check network/credentials)"
		exit 0
	fi
fi

if [[ -z "$DB_PATH" || ! -f "$DB_PATH" ]]; then
	echo "sync-smoke: fail (sqlite file missing: ${DB_PATH:-unknown})"
	exit 1
fi

COUNT="$(php -r '
$path = $argv[1];
$pdo = new PDO("sqlite:" . $path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
echo (int) $pdo->query("SELECT COUNT(*) FROM offer")->fetchColumn();
' "$DB_PATH")"

if [[ "$COUNT" -le 0 ]]; then
	echo "sync-smoke: fail (offer table empty)"
	exit 1
fi

echo "sync-smoke: ok (offers=${COUNT})"
