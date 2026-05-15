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

namespace App\Enum;

/**
 * Define firmware version schema.
 */
enum FirmwareVersionSchema: string
{
    case ANY_SCHEMA = 'anySchema';
    case SEMANTIC_VERSIONING = 'semanticVersioning';
    case V_SEMANTIC_VERSIONING = 'vSemanticVersioning';
    case EG_OS_SCHEMA = 'egOsSchema';
}
