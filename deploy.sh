#!/bin/bash
set -euo pipefail

VERSION="${1:-}"

if [ -z "$VERSION" ]; then
	echo "Usage: ./deploy.sh <version>"
	echo "Example: ./deploy.sh 22.0"
	echo "CI: triggered automatically on version tags (see .github/workflows/release.yml)"
	exit 1
fi

# Strip optional leading v so ZIP names stay swiss-parks-php-sdk-22.0.zip
VERSION="${VERSION#v}"

STAGING_DIR="./release-staging-$VERSION"
ZIP_NAME="swiss-parks-php-sdk-$VERSION.zip"

# Make temp folder
mkdir -p "$STAGING_DIR"

# Copy only files known to git (tracked or new, not ignored), so local config, SQLite files and logs never ship
# Skip files deleted in the working tree but still in the index (macOS openrsync has no --ignore-missing-args)
git ls-files -z --cached --others --exclude-standard swiss-parks-sdk index.php \
	| while IFS= read -r -d '' file; do
		if [ -f "$file" ]; then
			printf '%s\0' "$file"
		fi
	done \
	| rsync -a -0 --files-from=- ./ "$STAGING_DIR/"

# Ensure release output directory exists
mkdir -p ./releases

# Compress folder (swiss-parks-sdk/ and index.php at archive root)
rm -f deploy.zip
( cd "$STAGING_DIR" && zip -9 -r ../deploy.zip . )
mv ./deploy.zip "./releases/$ZIP_NAME"

# Remove temp folder
rm -Rf "$STAGING_DIR"

echo "Created ./releases/$ZIP_NAME"
