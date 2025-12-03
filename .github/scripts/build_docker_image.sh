#!/bin/bash

# $1 - Image name (one of composer, scep, frontend, core, backend, app, test)
# $2 - Pass "true" when image should be build

image="$1"
build="$2"

knownImages=("composer" "scep" "frontend" "core" "backend" "app" "test")

if [ -z "$image" ]; then
    echo "Image name is required"
    exit 1
fi

if [[ ! " ${knownImages[*]} " =~ [[:space:]]${image}[[:space:]] ]]; then
    echo "Unknown image '$image'"
    exit 1
fi

if [ "$build" != "true" ]; then
    exit 0
fi

echo "Building image for '$image'"
make "$image-build"