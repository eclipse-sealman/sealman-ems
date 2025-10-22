<?php

// Copyright (c) 2025 Contributors to the Eclipse Foundation.
//
// See the NOTICE file(s) distributed with this work for additional
// information regarding copyright ownership.
//
// This program and the accompanying materials are made available under the
// terms of the Apache License, Version 2.0 which is available at
// https://www.apache.org/licenses/LICENSE-2.0
//
// SPDX-License-Identifier: Apache-2.0

declare(strict_types=1);

namespace App\OpenApi;

/**
 * Class that stores repeated OpenAPI documentation strings.
 *
 * Approach can be changes as soon as it becomes cumbersome to maintain it.
 */
class OpenApiDocumentation
{
    /**
     * This description is used conditionally only for ROLE_VPN_ENDPOINTDEVICES.
     */
    public const FIELD_ROLE_ENDPOINTDEVICES = 'Field available for users allowed to manage endpoint devices.';

    /**
     * This description is used conditionally only for ROLE_VPN_ENDPOINTDEVICES.
     */
    public const FIELD_INDEXED_COLLECTION = 'Values should be indexed by ID of the related entity. In order to create a new entity use any unique string as index (eg. new_1 or 4b183c6). In order to delete an entity exclude it from the values.';

    public static function join(array $descriptions): string
    {
        return implode(' ', $descriptions);
    }
}
