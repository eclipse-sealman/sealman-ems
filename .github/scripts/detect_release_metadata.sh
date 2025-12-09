#!/bin/bash

# Copyright (c) 2025 Contributors to the Eclipse Foundation.
#
# See the NOTICE file(s) distributed with this work for additional
# information regarding copyright ownership.
#
# This program and the accompanying materials are made available under the
# terms of the Apache License, Version 2.0 which is available at
# https://www.apache.org/licenses/LICENSE-2.0
#
# SPDX-License-Identifier: Apache-2.0

# Read `.github/metadata/VERSION` and `.github/metadata/PREVIOUS_VERSION` to prepare release metadata as output for GitHub Actions
# Prepares the following outputs:
# - version: The current version
# - previousVersion: The previous released version (empty when release = "initial")
# - release: "none", "rc", "production" or "initial" (without a PREVIOUS_VERSION)
# - changesBase: The git SHA to use as base for change detection. Previous commit when release = "none" or "initial", or previous version (tag) for release = "rc" or "production"
# $1 - Pass "true" when there are changes to `.github/metadata/VERSION` or `.github/metadata/PREVIOUS_VERSION` which indicates a new release

changed="${1}"
version=$(cat .github/metadata/VERSION)
previousVersion=$(cat .github/metadata/PREVIOUS_VERSION)

if [ -z "$version" ]; then
    echo "Failed to read version"
    exit 1
fi

echo "version=$version"
echo "version=$version" >> $GITHUB_OUTPUT
echo "previousVersion=$previousVersion"
echo "previousVersion=$previousVersion" >> $GITHUB_OUTPUT

if [ "$changed" != "true" ]; then
    echo "release=none"
    echo "release=none" >> $GITHUB_OUTPUT
    echo "changesBase=$GITHUB_REF_NAME"
    echo "changesBase=$GITHUB_REF_NAME" >> $GITHUB_OUTPUT
elif  [ -z "$previousVersion" ]; then
    echo "release=initial"
    echo "release=initial" >> $GITHUB_OUTPUT
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

    # Prefix with 'v' as tags are prefixed with 'v'
    echo "changesBase=v$previousVersion"
    echo "changesBase=v$previousVersion" >> $GITHUB_OUTPUT
fi