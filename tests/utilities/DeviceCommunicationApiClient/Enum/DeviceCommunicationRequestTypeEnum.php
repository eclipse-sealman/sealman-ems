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

namespace Tests\Utilities\DeviceCommunicationApiClient\Enum;

enum DeviceCommunicationRequestTypeEnum: string
{
    case INITIAL = 'initial'; // Initial request to create a device in the system
    case DISABLED = 'disabled'; // Request to disable a device
    case FIRMWARE_RESPONSE = 'firmwareResponse'; // Request to device - expect successful response - 200 OK, and valid firmware response content type
    case SUCCESSFUL_RESPONSE = 'successfulResponse'; // Request to device - expect successful response - 200 OK, and valid response content type
    case UNAUTHORIZED_RESPONSE = 'unauthorizedResponse'; // Request to device - expect unauthorized response - 401 Unauthorized
    case FORBIDDEN_RESPONSE = 'forbiddenResponse'; // Request to device - expect forbidden response - 403 Forbidden
    case UNVALIDATED_RESPONSE = 'unvalidatedResponse'; // Request to device - no assertions on response are executed
}
