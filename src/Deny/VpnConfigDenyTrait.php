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
use App\Entity\User;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;

trait VpnConfigDenyTrait
{
    use AuthorizationCheckerTrait;
    use CertificateTypeHelperTrait;

    /**
     * This deny is used in DeviceController, UserController (standard usage) and in VpnController (custom usage).
     * VpnController lets you download your own OpenVPN configuration file (just for logged in user).
     */
    public function downloadVpnConfigDeny(User|Device $object): ?string
    {
        if ($object instanceof User) {
            if (!$this->isGranted('ROLE_ADMIN_VPN') && !$this->isGranted('ROLE_VPN')) {
                return 'accessDenied';
            }

            if (!$object->getRoleAdmin() && !$object->getRoleVpn()) {
                return 'notAvailable';
            }
        } else {
            if (!$this->isGranted('ROLE_ADMIN_VPN')) {
                return 'accessDenied';
            }
        }

        if (method_exists($object, 'getDeviceType')) {
            if (!$object->getDeviceType() || !$object->getDeviceType()->getIsVpnAvailable()) {
                return 'disabledInDeviceType';
            }
        }

        $certificate = $this->getVpnCertificate($object);

        if ($certificate) {
            if (!$certificate->hasCertificate()) {
                return 'noCertificate';
            }

            if (!$object->getVpnIp()) {
                return 'noVpnIp';
            }

            return null;
        }

        // object without certififcates so not User,Device,DeviceEndpointDevice - it shouldn't ask for config
        return 'notAvailable';
    }
}
