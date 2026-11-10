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
	--exclude=data \
	--exclude=log \
	--exclude=config.local.php \
	-r

# Include integration example
cp index.php "$STAGING_DIR/"

# Remove unused templates
rm -Rf "$STAGING_DIR/swiss-parks-sdk/template/nationalpark"
rm -Rf "$STAGING_DIR/swiss-parks-sdk/template/pfyn"

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
