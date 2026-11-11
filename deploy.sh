#!/bin/bash
set -euo pipefail

VERSION="${1:-}"

if [ -z "$VERSION" ]; then
	echo "Usage: ./deploy.sh <version>"
	echo "Example: ./deploy.sh 22"
	echo "CI: triggered automatically on version tags (see .github/workflows/release.yml)"
	exit 1
fi

# Strip optional leading v so ZIP names stay swiss-parks-php-sdk-22.zip
VERSION="${VERSION#v}"

STAGING_DIR="./release-staging-$VERSION"
ZIP_NAME="swiss-parks-php-sdk-$VERSION.zip"

# Make temp folder
mkdir -p "$STAGING_DIR/swiss-parks-sdk"

# Copy SDK runtime files only (no docs, releases, tests, or local dev config)
rsync swiss-parks-sdk/ "$STAGING_DIR/swiss-parks-sdk/" \
	--exclude=writable/data \
	--exclude=writable/log \
	--exclude=config/config.local.php \
	--exclude=data \
	--exclude=log \
	--exclude=helpers \
	-r

# Ensure empty writable dirs exist in the ZIP
mkdir -p "$STAGING_DIR/swiss-parks-sdk/writable/data" "$STAGING_DIR/swiss-parks-sdk/writable/log"
if [ -f swiss-parks-sdk/writable/data/.htaccess ]; then
	cp swiss-parks-sdk/writable/data/.htaccess "$STAGING_DIR/swiss-parks-sdk/writable/data/"
fi
if [ -f swiss-parks-sdk/writable/log/.htaccess ]; then
	cp swiss-parks-sdk/writable/log/.htaccess "$STAGING_DIR/swiss-parks-sdk/writable/log/"
fi
if [ -f swiss-parks-sdk/writable/data/index.html ]; then
	cp swiss-parks-sdk/writable/data/index.html "$STAGING_DIR/swiss-parks-sdk/writable/data/"
	cp swiss-parks-sdk/writable/log/index.html "$STAGING_DIR/swiss-parks-sdk/writable/log/" 2>/dev/null || cp swiss-parks-sdk/writable/data/index.html "$STAGING_DIR/swiss-parks-sdk/writable/log/"
fi

# Include integration example
cp index.php "$STAGING_DIR/"

# Remove unused templates (kept in git only)
rm -Rf "$STAGING_DIR/swiss-parks-sdk/templates/nationalpark"
rm -Rf "$STAGING_DIR/swiss-parks-sdk/templates/pfyn"

# Delete all subversion files
find "$STAGING_DIR" -name ".svn" -exec rm -rf {} \;

# Delete all .DS_Store files
find "$STAGING_DIR" -name ".DS_Store" -exec rm -rf {} \;

# Ensure release output directory exists
mkdir -p ./releases

# Compress folder (swiss-parks-sdk/ and index.php at archive root)
rm -f deploy.zip
( cd "$STAGING_DIR" && zip -9 -r ../deploy.zip . )
mv ./deploy.zip "./releases/$ZIP_NAME"

# Remove temp folder
rm -Rf "$STAGING_DIR"

echo "Created ./releases/$ZIP_NAME"
