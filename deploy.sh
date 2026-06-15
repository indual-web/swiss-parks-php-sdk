#!/bin/bash

VERSION=$1

if [ -z "$VERSION" ]; then
	echo "Usage: ./deploy.sh <version>"
	exit 1
fi

STAGING_DIR="./release-staging-$VERSION"

# Make temp folder
mkdir -p "$STAGING_DIR/swiss-parks-sdk"

# Copy SDK runtime files only (no docs, releases, tests, or local dev config)
rsync swiss-parks-sdk/ "$STAGING_DIR/swiss-parks-sdk/" \
	--exclude=data \
	--exclude=log \
	--exclude=config.local.php \
	-r

# Include integration example
cp example.php "$STAGING_DIR/"

# Remove unused templates
rm -Rf "$STAGING_DIR/swiss-parks-sdk/template/nationalpark"
rm -Rf "$STAGING_DIR/swiss-parks-sdk/template/pfyn"

# Delete all subversion files
find "$STAGING_DIR" -name ".svn" -exec rm -rf {} \;

# Delete all .DS_Store files
find "$STAGING_DIR" -name ".DS_Store" -exec rm -rf {} \;

# Ensure release output directory exists
mkdir -p ./releases

# Compress folder (swiss-parks-sdk/ and example.php at archive root)
rm -f deploy.zip
( cd "$STAGING_DIR" && zip -9 -r ../deploy.zip . )
mv ./deploy.zip ./releases/swiss-parks-php-sdk-$VERSION.zip

# Remove temp folder
rm -Rf "$STAGING_DIR"
