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

use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\VpnConnection;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\UserTrait;

trait VpnCloseConnectionDenyTrait
{
    use AuthorizationCheckerTrait;
    use ConfigurationManagerTrait;
    use UserTrait;

    public function vpnCloseConnectionDeny(Device|DeviceEndpointDevice|VpnConnection $object): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN_VPN') && !$this->isGranted('ROLE_VPN')) {
            return 'accessDenied';
        }

        if (method_exists($object, 'getDeviceType')) {
            if (!$object->getDeviceType() || !$object->getDeviceType()->getIsVpnAvailable()) {
                return 'disabledInDeviceType';
            }
        }

        if ($object instanceof VpnConnection) {
            if (!$this->isGranted('ROLE_ADMIN_VPN')) {
                if ($object->getUser() !== $this->getUser()) {
                    return 'accessDenied';
                }
            }

            if ($object->getPermanent()) {
                return 'connectionNotAvailable';
            }

            if ($this->configurationManager->isVpnSecuritySuiteBlocked()) {
                return 'vpnSecuritySuiteBlocked';
            }

            if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
                return 'vpnSecuritySuiteInvalidConfiguration';
            }

            return null;
        }

        if (0 == $object->getVpnConnections()->count()) {
            return 'connectionNotAvailable';
        }

        if (!$this->isGranted('ROLE_ADMIN_VPN')) {
            $canClose = false;
            // Optimisation to limit amount of loops
            foreach ($this->getUser()->getVpnConnections() as $connection) {
                if ($connection->getTarget() == $object && !$connection->getPermanent()) {
                    // user is connected to this device
                    $canClose = true;
                }
            }
            if (!$canClose) {
                return 'connectionNotAvailable';
            }
        } else {
            // admin can close any connection
            $canClose = false;
            foreach ($object->getVpnConnections() as $connection) {
                if (!$connection->getPermanent()) {
                    // Admin can close any regular connection
                    $canClose = true;
                }
            }
            if (!$canClose) {
                return 'connectionNotAvailable';
            }
        }

        if ($this->configurationManager->isVpnSecuritySuiteBlocked()) {
            return 'vpnSecuritySuiteBlocked';
        }

        if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
            return 'vpnSecuritySuiteInvalidConfiguration';
        }

        return null;
    }
}
