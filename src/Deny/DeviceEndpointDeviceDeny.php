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

use App\Entity\DeviceEndpointDevice;
use App\Service\Helper\AuthorizationCheckerTrait;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;

class DeviceEndpointDeviceDeny extends AbstractApiObjectDeny implements VpnOpenConnectionDenyInterface, VpnCloseConnectionDenyInterface
{
    use VpnOpenConnectionDenyTrait;
    use VpnCloseConnectionDenyTrait;
    use AuthorizationCheckerTrait;

    public function deleteDeny(DeviceEndpointDevice $endpointDevice): ?string
    {
        if ($this->isGranted('ROLE_ADMIN_VPN')) {
            return null;
        }

        if ($this->isGranted('ROLE_VPN_ENDPOINTDEVICES')) {
            return null;
        }

        return 'accessDenied';
    }
}
