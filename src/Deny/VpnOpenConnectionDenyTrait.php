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
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\UserTrait;
use App\Service\Trait\CertificateTypeHelperTrait;

trait VpnOpenConnectionDenyTrait
{
    use CertificateTypeHelperTrait;
    use ConfigurationManagerTrait;
    use UserTrait;
    use AuthorizationCheckerTrait;

    public function vpnOpenConnectionDeny(Device|DeviceEndpointDevice $object): ?string
    {
        if ($this->configurationManager->isVpnSecuritySuiteBlocked()) {
            return 'vpnSecuritySuiteBlocked';
        }

        if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
            return 'vpnSecuritySuiteInvalidConfiguration';
        }

        if (!$this->isGranted('ROLE_ADMIN_VPN') && !$this->isGranted('ROLE_VPN')) {
            return 'accessDenied';
        }

        if ($this->isGranted('ROLE_VPN')) {
            if ($object instanceof Device) {
                $accessDenied = true;
                foreach ($object->getAccessTags() as $accessTag) {
                    if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                        $accessDenied = false;
                    }
                }
                if ($accessDenied) {
                    return 'accessDenied';
                }
            }
        }

        $parentDevice = $object;
        if ($object instanceof DeviceEndpointDevice) {
            $parentDevice = $object->getDevice();
        }

        if (!$parentDevice || !$parentDevice->getDeviceType() || !$parentDevice->getDeviceType()->getIsVpnAvailable()) {
            return 'disabledInDeviceType';
        }

        $certificate = $this->getVpnCertificate($parentDevice);

        if (!$certificate || !$certificate->hasCertificate()) {
            return 'noCertificate';
        }

        if (!$parentDevice->getVpnIp()) {
            return 'noVpnIp';
        }

        if ($object instanceof DeviceEndpointDevice) {
            if (!$object->getPhysicalIp()) {
                return 'noPhysicalIp';
            }
        }

        if (!$parentDevice->getVpnConnected()) {
            return 'notConnectedToVpn';
        }

        if (!$this->getUser()->getVpnConnected()) {
            return 'userNotConnectedToVpn';
        }

        foreach ($object->getVpnConnections() as $connection) {
            if ($connection->getUser() == $this->getUser()) {
                if ($object instanceof DeviceEndpointDevice) {
                    if ($connection->getEndpointDevice() == $object) {
                        return 'alreadyConnected';
                    }
                } else {
                    if ($connection->getDevice() == $object) {
                        return 'alreadyConnected';
                    }
                }
            }
        }

        return null;
    }
}
