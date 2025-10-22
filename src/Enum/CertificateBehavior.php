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
 * Enum decribes behaviour of certificate when entity cointaining certificate is enabled or disabled
 * none:
 *  Do nothing automaticaly
 * onDemand:
 *  User has choice using $generateCertificate $revokeCertificate flag
 * auto:
 *  When enabled make sure certificate is generated
 *  When disabled make sure certificate is revoked.
 * specific:
 *  Automatic behaviour is defined programically specifically for certain certificate categories - not available for user to choose
 *  - option added to ease frontend development and consistency.
 */
enum CertificateBehavior: string
{
    case NONE = 'none';
    case ON_DEMAND = 'onDemand';
    case AUTO = 'auto';
    case SPECIFIC = 'specific';
}
