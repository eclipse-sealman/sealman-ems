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

ARG CORE_REGISTRY_IMAGE
ARG CORE_VERSION
ARG COMPOSER_REGISTRY_IMAGE
ARG COMPOSER_VERSION

FROM ${COMPOSER_REGISTRY_IMAGE}:${COMPOSER_VERSION} AS composer

FROM ${CORE_REGISTRY_IMAGE}:${CORE_VERSION}

# Copy leverages .dockerignore to copy ONLY necessary files
COPY . /var/www/application

WORKDIR /var/www/application

RUN --mount=type=bind,from=composer,source=/var/www/html/composer.phar,target=/usr/local/bin/composer \
    export COMPOSER_ALLOW_SUPERUSER=1; \
    composer install --no-cache --no-dev --optimize-autoloader; \
    chown -R www-data:www-data \
        /var/www/application/var/log \
        /var/www/application/var/cache \
        /var/www/application/public \
        /var/www/application/private \
        /var/lib/nginx; \
    php bin/console --no-debug app:licenses:composer-dump; \
    # Remove var/cache/* as it was populated by running bin/console command
    rm -rf /var/www/application/var/cache/*
