#!/bin/bash

VERSION=$1

if [ -z "$VERSION" ]; then
	echo "Usage: ./deploy.sh <version>"
	exit 1
fi

# Make temp folder
mkdir -p ./parks_api_$VERSION

# Copy SDK runtime files only (no docs, releases, tests, or local dev config)
rsync parks_api/ ./parks_api_$VERSION/parks_api/ \
	--exclude=data \
	--exclude=log \
	--exclude=config.local.php \
	-r

# Include integration example
cp example.php ./parks_api_$VERSION/

# Remove unused templates
rm -Rf ./parks_api_$VERSION/parks_api/template/nationalpark
rm -Rf ./parks_api_$VERSION/parks_api/template/pfyn

# Delete all subversion files
find ./parks_api_$VERSION -name ".svn" -exec rm -rf {} \;

# Delete all .DS_Store files
find ./parks_api_$VERSION -name ".DS_Store" -exec rm -rf {} \;

# Ensure release output directory exists
mkdir -p ./releases

# Remove old deploy.zip if exists
rm -f deploy.zip

# Compress folder
zip -9 -r deploy.zip ./parks_api_$VERSION/*
mv ./deploy.zip ./releases/Parks-API-$VERSION.zip

# Remove temp folder
rm -Rf ./parks_api_$VERSION
