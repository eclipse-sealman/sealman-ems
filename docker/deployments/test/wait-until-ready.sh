#!/bin/sh

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

# Script waits until the application is ready to accept requests (success exit code) or until timeout of 60s is reached (error exit code)

timestamp=$(date -Iseconds)
retries=0
maxRetries=60

while [ $retries -lt $maxRetries ]; do
    if docker compose -f docker/deployments/test/compose.yaml logs --since=$timestamp application | grep -q "php-fpm entered RUNNING state"; then
        echo "Application is ready"
        exit 0
    fi

    echo "Application is NOT ready yet ($retries/$maxRetries seconds)"
    retries=$((retries+1))
    sleep 1
done

exit 1
