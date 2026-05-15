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

namespace Tests\Utilities\DeviceCommunicationAbstract\Enum;

enum DeviceUserState: string
{
    case VALID = 'valid';
    case INVALID_PASSWORD = 'invalidPassword';
    case DISABLED = 'disabled';
    case NOT_EXISTS = 'notExists';
    case NO_ROLE_DEVICE = 'noRoleDevice';
    case NO_DEVICE_TYPE_ROLE = 'noDeviceTypeRole';
}
