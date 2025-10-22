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

ARG APP_REGISTRY_IMAGE
ARG APP_VERSION
ARG COMPOSER_REGISTRY_IMAGE
ARG COMPOSER_VERSION

FROM ${COMPOSER_REGISTRY_IMAGE}:${COMPOSER_VERSION} AS composer

FROM ${APP_REGISTRY_IMAGE}:${APP_VERSION}

# Copy leverages .dockerignore to copy ONLY necessary files
COPY . /var/www/application

RUN --mount=type=bind,from=composer,source=/var/www/html/composer.phar,target=/usr/local/bin/composer \
    export COMPOSER_ALLOW_SUPERUSER=1; \
    composer install --no-cache --optimize-autoloader; \
    rm -rf /var/www/application/var/cache/*