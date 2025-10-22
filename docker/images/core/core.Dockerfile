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

FROM php:8.4.11-fpm-alpine3.21

RUN apk add --no-cache \ 
    # WWW server
    nginx \ 
    # Monitor and control processes
    supervisor \ 
    # Support for Let's Encrypt certificates on manually-administrated websites to enable HTTPS
    certbot \ 
    # Shell (i.e. brings array support for scripts)
    bash \ 
    # Includes mysqldump
    mysql-client \
    # Support for timezone on alpine. Utilized by TZ environmental variable
    tzdata \ 
    # Required by gd 
    libpng-dev \
    # Required by intl
    icu-dev \
    # Required by zip
    libzip-dev\ 
    # Data compression used by maintenance scripts. Also required by zip
    zip\
    # Log rotate
    logrotate\
    # Install required PHP extentions
    && docker-php-ext-install pdo_mysql gd intl zip

COPY /docker/images/core/assets/licenses /var/www/application/licenses
RUN --mount=type=bind,source=/docker/images/core/assets/licenses-apk-dump.sh,target=/var/www/application/licenses/licenses-apk-dump.sh \
    /var/www/application/licenses/licenses-apk-dump.sh
