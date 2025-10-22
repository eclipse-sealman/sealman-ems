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

namespace App\Deny;

use App\Entity\DeviceTypeSecret;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;

class DeviceTypeSecretDeny extends AbstractApiObjectDeny
{
    // Access is limited to ROLE_ADMIN
    // Admin has full access to this entity

    public function deleteDeny(DeviceTypeSecret $deviceTypeSecret): ?string
    {
        if ($deviceTypeSecret->getDeviceType()->getDeviceTypeSecretCredential() == $deviceTypeSecret) {
            return 'usedAsCredential';
        }

        return null;
    }
}
