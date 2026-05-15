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

use App\Entity\DeviceTypeHardware;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;

class DeviceTypeHardwareDeny extends AbstractApiObjectDeny
{
    public function deleteDeny(DeviceTypeHardware $deviceTypeHardware): ?string
    {
        if (count($deviceTypeHardware->getFirmwareHardwareFiles()) > 0) {
            return 'delete.usedByFirmwareHardwareFile';
        }

        return null;
    }
}
