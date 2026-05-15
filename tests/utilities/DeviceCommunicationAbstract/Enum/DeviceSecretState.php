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

enum DeviceSecretState: string
{
    case VALID = 'valid';
    case INVALID_PASSWORD = 'invalidPassword';
    case INVALID_PASSWORD_SAME_AS_ENCRYPTED = 'invalidPasswordSameAsEncypted';
    case NOT_IN_DEVICE_TYPE_CREDENTIAL = 'notInDeviceTypeCredential';
    case DIFFERENT_DEVICE_TYPE = 'differentDeviceType';
    case NOT_EXISTS = 'notExists';
    case EXPIRED = 'expired';
    case WILL_BE_RENEWED = 'willBeRenewed';
    case NAME_SAME_AS_USERNAME = 'nameSameAsUsername';
}
