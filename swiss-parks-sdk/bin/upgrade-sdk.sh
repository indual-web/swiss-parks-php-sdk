#!/bin/bash
set -euo pipefail

GITHUB_REPO="${PARKS_API_GITHUB_REPO:-indual-web/swiss-parks-php-sdk}"
VERSION="${1:-latest}"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SDK_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"



show_usage()
{

	cat <<EOF
Usage: bash upgrade-sdk.sh [version]

  version   Release version (e.g. 22, 21.1) or "latest" (default)

Downloads a Swiss Parks PHP SDK release from GitHub Releases, replaces SDK core
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



resolve_download_urls()
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

	echo "https://github.com/${GITHUB_REPO}/releases/download/${version}/swiss-parks-php-sdk-${version}.zip"
	echo "https://github.com/${GITHUB_REPO}/releases/download/${version}/Parks-API-${version}.zip"
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

if [ ! -f "$SDK_DIR/autoload.php" ]; then
	echo "Error: SDK directory not found at $SDK_DIR" >&2
	exit 1
fi

DOWNLOAD_URLS="$(resolve_download_urls "$VERSION")"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_DIR="$(dirname "$SDK_DIR")/swiss-parks-sdk-backup-${TIMESTAMP}"
TMP_DIR="$(mktemp -d)"
ZIP_FILE="$TMP_DIR/release.zip"

cleanup()
{

	rm -rf "$TMP_DIR"

}

trap cleanup EXIT

echo "Downloading release..."
DOWNLOAD_OK=0
while IFS= read -r url; do
	[ -z "$url" ] && continue
	if curl -fsSL "$url" -o "$ZIP_FILE"; then
		DOWNLOAD_OK=1
		break
	fi
done <<< "$DOWNLOAD_URLS"

if [ "$DOWNLOAD_OK" -ne 1 ]; then
	echo "Error: download failed for version $VERSION" >&2
	echo "Tried:" >&2
	while IFS= read -r url; do
		[ -z "$url" ] && continue
		echo "  $url" >&2
	done <<< "$DOWNLOAD_URLS"
	echo "Check the version and that the release exists on GitHub." >&2
	exit 1
fi

echo "Creating backup at $BACKUP_DIR"
cp -a "$SDK_DIR" "$BACKUP_DIR"

echo "Extracting release..."
unzip -q "$ZIP_FILE" -d "$TMP_DIR/extracted"

EXTRACT_ROOT="$(find "$TMP_DIR/extracted" -mindepth 1 -maxdepth 1 -type d | head -1)"

if [ -f "$EXTRACT_ROOT/autoload.php" ]; then
	SRC_DIR="$EXTRACT_ROOT"
elif [ -f "$EXTRACT_ROOT/swiss-parks-sdk/autoload.php" ]; then
	SRC_DIR="$EXTRACT_ROOT/swiss-parks-sdk"
elif [ -f "$EXTRACT_ROOT/parks_api/autoload.php" ]; then
	SRC_DIR="$EXTRACT_ROOT/parks_api"
else
	echo "Error: invalid release ZIP (missing swiss-parks-sdk/autoload.php)." >&2
	exit 1
fi

echo "Updating SDK core files..."
rsync -a "$SRC_DIR/autoload.php" "$SDK_DIR/"
rsync -a "$SRC_DIR/classes/" "$SDK_DIR/classes/"
rsync -a "$SRC_DIR/database/" "$SDK_DIR/database/"
rsync -a "$SRC_DIR/helpers/" "$SDK_DIR/helpers/"
rsync -a "$SRC_DIR/language/" "$SDK_DIR/language/"
rsync -a "$SRC_DIR/scripts/" "$SDK_DIR/scripts/"

if [ -d "$SRC_DIR/bin" ]; then
	rsync -a "$SRC_DIR/bin/" "$SDK_DIR/bin/"
fi

if [ -d "$SRC_DIR/template/standard" ]; then
	rsync -a "$SRC_DIR/template/standard/" "$SDK_DIR/template/standard/"
fi

if [ -d "$SRC_DIR/template/parks.swiss" ]; then
	rsync -a "$SRC_DIR/template/parks.swiss/" "$SDK_DIR/template/parks.swiss/"
fi

INSTALLED_VERSION="$(grep -E "define\('API_VERSION'," "$SDK_DIR/autoload.php" | sed -E "s/.*API_VERSION', *([^)]+)\).*/\1/")"

echo "SDK files updated to API version $INSTALLED_VERSION."
echo "Backup: $BACKUP_DIR"

if [ "${PARKS_API_SKIP_MIGRATE:-0}" = "1" ]; then
	echo "Skipping migrate.php (PARKS_API_SKIP_MIGRATE=1)."
	exit 0
fi

echo "Running migrate.php..."
if php "$SDK_DIR/scripts/migrate.php"; then
	echo "Upgrade complete."
	exit 0
fi

echo "Error: migrate.php failed. Restore from backup if needed: $BACKUP_DIR" >&2
	exit 1
