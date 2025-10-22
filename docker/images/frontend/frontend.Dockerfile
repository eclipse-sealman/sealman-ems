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

FROM node:20-alpine AS frontend

COPY . /var/www/application

WORKDIR /var/www/application/app

RUN npm install
# Extend max memory size of old memory section (V8 CLI option)
RUN NODE_OPTIONS="--max-old-space-size=4096" npm run build

RUN mkdir -p /var/www/application/licenses
RUN npm install -g license-checker
RUN license-checker --csv --customPath /var/www/application/docker/images/frontend/assets/license-checker-format.json --out /var/www/application/licenses/javascript-licenses.csv
# Remove first line as those are column names
RUN sed -i '1d' /var/www/application/licenses/javascript-licenses.csv
