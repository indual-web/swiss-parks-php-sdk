#!/bin/bash
set -euo pipefail

GITHUB_REPO="${PARKS_API_GITHUB_REPO:-indual-web/swiss-parks-php-sdk}"
VERSION="${1:-latest}"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PARKS_API_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"



show_usage()
{

	cat <<EOF
Usage: bash upgrade-sdk.sh [version]

  version   Release version (e.g. 22, 21.1) or "latest" (default)

Downloads the Parks-API release from GitHub Releases, replaces SDK core
files, preserves config.php, custom/, data/, and log/, then runs migrate.php.

Environment:
  PARKS_API_GITHUB_REPO   GitHub repo (default: indual-web/swiss-parks-php-sdk)
  PARKS_API_SKIP_MIGRATE  Set to 1 to skip migrate.php after the file update
EOF

}



require_command()
{

	if ! command -v "$1" >/dev/null 2>&1; then
		echo "Error: required command not found: $1" >&2
		exit 1
	fi

}



resolve_download_url()
{

	local version="$1"

	if [ "$version" = "latest" ]; then
		GITHUB_REPO="$GITHUB_REPO" php -r '
			$repo = getenv("GITHUB_REPO");
			$ctx = stream_context_create([
				"http" => [
					"header" => "User-Agent: parks-api-upgrade-sdk\r\nAccept: application/vnd.github+json\r\n",
					"timeout" => 30,
				],
			]);
			$json = @file_get_contents("https://api.github.com/repos/" . $repo . "/releases/latest", false, $ctx);
			if ($json === false) {
				fwrite(STDERR, "Error: could not fetch latest release from GitHub.\n");
				exit(1);
			}
			$release = json_decode($json);
			if (empty($release->assets)) {
				fwrite(STDERR, "Error: latest GitHub release has no assets.\n");
				exit(1);
			}
			foreach ($release->assets as $asset) {
				if (str_ends_with($asset->name, ".zip")) {
					echo $asset->browser_download_url;
					exit(0);
				}
			}
			fwrite(STDERR, "Error: latest GitHub release has no ZIP asset.\n");
			exit(1);
		'
		return
	fi

	echo "https://github.com/${GITHUB_REPO}/releases/download/v${version}/Parks-API-${version}.zip"

}



if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
	show_usage
	exit 0
fi

require_command curl
require_command unzip
require_command rsync
require_command php

if [ ! -f "$PARKS_API_DIR/autoload.php" ]; then
	echo "Error: parks_api directory not found at $PARKS_API_DIR" >&2
	exit 1
fi

DOWNLOAD_URL="$(resolve_download_url "$VERSION")"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$(dirname "$PARKS_API_DIR")/parks_api-backup-${TIMESTAMP}"
TMP_DIR="$(mktemp -d)"
ZIP_FILE="$TMP_DIR/release.zip"

cleanup()
{

	rm -rf "$TMP_DIR"

}

trap cleanup EXIT

echo "Downloading release..."
if ! curl -fsSL "$DOWNLOAD_URL" -o "$ZIP_FILE"; then
	echo "Error: download failed ($DOWNLOAD_URL)" >&2
	echo "Check the version and that the release exists on GitHub." >&2
	exit 1
fi

echo "Creating backup at $BACKUP_DIR"
cp -a "$PARKS_API_DIR" "$BACKUP_DIR"

echo "Extracting release..."
unzip -q "$ZIP_FILE" -d "$TMP_DIR/extracted"

EXTRACT_ROOT="$(find "$TMP_DIR/extracted" -mindepth 1 -maxdepth 1 -type d | head -1)"
SRC_DIR="$EXTRACT_ROOT/parks_api"

if [ ! -f "$SRC_DIR/autoload.php" ]; then
	echo "Error: invalid release ZIP (missing parks_api/autoload.php)." >&2
	exit 1
fi

echo "Updating SDK core files..."
rsync -a "$SRC_DIR/autoload.php" "$PARKS_API_DIR/"
rsync -a "$SRC_DIR/classes/" "$PARKS_API_DIR/classes/"
rsync -a "$SRC_DIR/database/" "$PARKS_API_DIR/database/"
rsync -a "$SRC_DIR/helpers/" "$PARKS_API_DIR/helpers/"
rsync -a "$SRC_DIR/language/" "$PARKS_API_DIR/language/"
rsync -a "$SRC_DIR/scripts/" "$PARKS_API_DIR/scripts/"

if [ -d "$SRC_DIR/bin" ]; then
	rsync -a "$SRC_DIR/bin/" "$PARKS_API_DIR/bin/"
fi

if [ -d "$SRC_DIR/template/standard" ]; then
	rsync -a "$SRC_DIR/template/standard/" "$PARKS_API_DIR/template/standard/"
fi

if [ -d "$SRC_DIR/template/parks.swiss" ]; then
	rsync -a "$SRC_DIR/template/parks.swiss/" "$PARKS_API_DIR/template/parks.swiss/"
fi

INSTALLED_VERSION="$(grep -E "define\('API_VERSION'," "$PARKS_API_DIR/autoload.php" | sed -E "s/.*API_VERSION', *([^)]+)\).*/\1/")"

echo "SDK files updated to API version $INSTALLED_VERSION."
echo "Backup: $BACKUP_DIR"

if [ "${PARKS_API_SKIP_MIGRATE:-0}" = "1" ]; then
	echo "Skipping migrate.php (PARKS_API_SKIP_MIGRATE=1)."
	exit 0
fi

echo "Running migrate.php..."
if php "$PARKS_API_DIR/scripts/migrate.php"; then
	echo "Upgrade complete."
	exit 0
fi

echo "Error: migrate.php failed. Restore from backup if needed: $BACKUP_DIR" >&2
	exit 1
