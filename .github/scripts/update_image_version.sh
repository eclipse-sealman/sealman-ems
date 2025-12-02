#!/bin/bash

# $1 - Image name (one of composer, scep, frontend, core, backend, app, test)
# $2 - Image version (i.e. 1.0.0, 2.1.1-rc1, 21f2f404)
# $3 - Pass "true" when image version should be changed

image="$1"
version="$2"
changed="$3"

knownImages=("composer" "scep" "frontend" "core" "backend" "app" "test")

if [ -z "$image" ]; then
    echo "Image name is required"
    exit 1
fi

if [[ ! " ${knownImages[*]} " =~ [[:space:]]${image}[[:space:]] ]]; then
    echo "Unknown image '$image'"
    exit 1
fi

if [ -z "$version" ]; then
    echo "Image version is required"
    exit 1
fi

if [ "$changed" != "true" ]; then
    echo "Image version for '$image' will not be changed"
    exit 0
fi

echo "Updating image version for '$image' to '$version'"
imageUppercase=${image^^}
sed -i "s/^${imageUppercase}_VERSION=.*/${imageUppercase}_VERSION=${version}/" "../../docker/images/${image}/.env"