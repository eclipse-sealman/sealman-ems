#!/bin/bash

# $1 - Image name (one of composer, scep, frontend, core, backend, app, test)
# $2 - Pass "true" when image should be pushed

image="$1"
push="$2"

knownImages=("composer" "scep" "frontend" "core" "backend" "app" "test")

if [ -z "$image" ]; then
    echo "Image name is required"
    exit 1
fi

if [[ ! " ${knownImages[*]} " =~ [[:space:]]${image}[[:space:]] ]]; then
    echo "Unknown image '$image'"
    exit 1
fi

if [ "$push" != "true" ]; then
    exit 0
fi

echo "Pushing image for '$image'"
make "$image-push"