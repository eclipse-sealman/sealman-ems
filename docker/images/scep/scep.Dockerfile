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

# Keep the same base image in core docker
FROM php:8.4.11-fpm-alpine3.21 AS scep

RUN mkdir -p /var/www/application/bin
COPY /docker/images/scep/assets/scep.c /var/www/application/bin/

WORKDIR /var/www/application/bin

RUN apk add --no-cache \
    # C++ compiler
    gcc \
    # C++ compiler libstd headers
    g++ \
    # openssl library headers
    openssl-dev
RUN gcc scep.c -o scep -lcrypto

