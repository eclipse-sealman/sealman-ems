#!/bin/bash

# $1 - Pass "true" when there are changes to `.github/workflows/VERSION` or `.github/workflows/PREVIOUS_VERSION` which indicates a new release

# Read `.github/workflows/VERSION` and `.github/workflows/PREVIOUS_VERSION` to prepare release metadata as output for GitHub Actions
# Prepares the following outputs:
# - version: The current version
# - previousVersion: The previous released version
# - isRelease: "true" if this is a release, "false" otherwise
# - isProductionRelease: "true" if this is a production release, "false" otherwise
# - changesBase: The git SHA or previous version to use as base for change detection

changed="${1}"
version=$(cat .github/workflows/VERSION)
previousVersion=$(cat .github/workflows/PREVIOUS_VERSION)

if [ -z "$version" ] || [ -z "$previousVersion" ]; then
    echo "Failed to read version or previous version"
    exit 1
fi

echo "version=$version" >> $GITHUB_OUTPUT
echo "previousVersion=$previousVersion" >> $GITHUB_OUTPUT

if [ "$changed" != "true" ]; then
    echo "isRelease=false" >> $GITHUB_OUTPUT
    echo "isProductionRelease=false" >> $GITHUB_OUTPUT
    echo "changesBase=$GITHUB_SHA" >> $GITHUB_OUTPUT
else
    echo "isRelease=true" >> $GITHUB_OUTPUT

    if [[ "$version" == *"-rc"* ]]; then
        echo "isProductionRelease=false" >> $GITHUB_OUTPUT
    else
        echo "isProductionRelease=true" >> $GITHUB_OUTPUT
    fi

    echo "changesBase=$previousVersion" >> $GITHUB_OUTPUT
fi