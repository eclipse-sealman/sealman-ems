#!/bin/bash

# Updates `.github/metadata/VERSION` and `.github/metadata/PREVIOUS_VERSION` with a new version. Prepared to keep consistent commit messages.
# Executes git add and git commit with a message. Does NOT push the changes.
# $1 - New version for `.github/metadata/VERSION`

newVersion="${1}"
version=$(cat .github/metadata/VERSION)
previousVersion=$(cat .github/metadata/PREVIOUS_VERSION)

if [ -z "$newVersion" ]; then
    echo "New version is required"
    exit 1
fi

echo "Updating .github/metadata/VERSION from '$version' to '$newVersion'"
echo "$newVersion" > .github/metadata/VERSION
echo "Updating .github/metadata/PREVIOUS_VERSION from '$previousVersion' to '$version'"
echo "$version" > .github/metadata/PREVIOUS_VERSION

git add .github/metadata/VERSION .github/metadata/PREVIOUS_VERSION
git commit -m "Update VERSION from $version to $newVersion and PREVIOUS_VERSION from $previousVersion to $version"