#!/bin/bash

# Make temp folder
mkdir -p ./parks_api_$1

# Copy all files into temp location
rsync ./* parks_api_$1/ --exclude=parks_api_$1 --exclude=parks_api/database -r

# Add current database dump
mkdir -p ./parks_api_$1/parks_api/database
rsync ./parks_api/database/database-$1.sql ./parks_api_$1/parks_api/database
mv ./parks_api_$1/parks_api/database/database-$1.sql ./parks_api_$1/parks_api/database/database.sql

# Remove unused files
rm -f ./parks_api_$1/deploy.sh
rm -Rf ./parks_api_$1/zip
rm -Rf ./parks_api_$1/parks_api/log/*

# Remove unused templates
rm -Rf ./parks_api_$1/parks_api/template/nationalpark
rm -Rf ./parks_api_$1/parks_api/template/pfyn

# Delete all subversion files
find ./parks_api_$1 -name ".svn" -exec rm -rf {} \;

# Delete all .DS_Store files
find ./parks_api_$1 -name ".DS_Store" -exec rm -rf {} \;

# Remove old deploy.zip if exists
rm deploy.zip

# compress folder
zip -9 -r deploy.zip ./parks_api_$1/* -x .svn
mv ./deploy.zip ./zip/Parks-API-$1.zip

# Remove temp folder
rm -Rf ./parks_api_$1