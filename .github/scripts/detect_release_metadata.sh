#!/bin/bash

# $1 - Pass "true" when there are changes to `.github/metadata/VERSION` or `.github/metadata/PREVIOUS_VERSION` which indicates a new release

# Read `.github/metadata/VERSION` and `.github/metadata/PREVIOUS_VERSION` to prepare release metadata as output for GitHub Actions
# Prepares the following outputs:
# - version: The current version
# - previousVersion: The previous released version
# - release: "no", "rc" or "production"
# - changesBase: The git SHA to use as base for change detection. Previous commit when release = "no", or previous version (tag) for release = "rc" or "production"

changed="${1}"
version=$(cat .github/metadata/VERSION)
previousVersion=$(cat .github/metadata/PREVIOUS_VERSION)

if [ -z "$version" ] || [ -z "$previousVersion" ]; then
    echo "Failed to read version or previous version"
    exit 1
fi

echo "version=$version"
echo "version=$version" >> $GITHUB_OUTPUT
echo "previousVersion=$previousVersion"
echo "previousVersion=$previousVersion" >> $GITHUB_OUTPUT

if [ "$changed" != "true" ]; then
    echo "release=no"
    echo "release=no" >> $GITHUB_OUTPUT
    echo "changesBase=$GITHUB_REF_NAME"
    echo "changesBase=$GITHUB_REF_NAME" >> $GITHUB_OUTPUT
else
    if [[ "$version" == *"-rc"* ]]; then
        echo "release=rc"
        echo "release=rc" >> $GITHUB_OUTPUT
    else
        echo "release=production"
        echo "release=production" >> $GITHUB_OUTPUT
    fi

    # Prefix with 'v' as we are tagging versions prefixed with 'v'
    echo "changesBase=v$previousVersion"
    echo "changesBase=v$previousVersion" >> $GITHUB_OUTPUT
fi