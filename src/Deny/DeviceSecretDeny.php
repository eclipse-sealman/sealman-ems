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

use App\Entity\DeviceSecret;
use App\Enum\SecretValueBehaviour;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuthorizationCheckerTrait;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;

class DeviceSecretDeny extends AbstractApiObjectDeny
{
    use AuthorizationCheckerTrait;
    use SecurityHelperTrait;

    public const CREATE = 'create';
    public const SHOW = 'show';
    public const SHOW_VARIABLES = 'showVariables';
    public const CLEAR = 'clear';
    public const ENABLE_FORCE_RENEWAL = 'enableForceRenewal';
    public const DISABLE_FORCE_RENEWAL = 'disableForceRenewal';

    protected function checkUserAccess(DeviceSecret $deviceSecret): ?string
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return null;
        }

        // Only ROLE_ADMIN ROLE_SMARTEMS OR ROLE_VPN can have access
        if (!$this->isGranted('ROLE_SMARTEMS') && !$this->isGranted('ROLE_VPN')) {
            return 'accessDenied';
        }

        // Check device value if not existent - something failed
        if (!$deviceSecret->getDevice()) {
            return 'accessDenied';
        }
        // Check deviceTypeSecret value if not existent - something failed
        if (!$deviceSecret->getDeviceTypeSecret()) {
            return 'accessDenied';
        }

        // Check device access
        if (!$this->isAllDevicesGranted()) {
            $hasDeviceAccess = false;
            foreach ($this->getUser()->getAccessTags() as $userAccessTag) {
                if ($deviceSecret->getDevice()->getAccessTags()->contains($userAccessTag)) {
                    $hasDeviceAccess = true;
                    break;
                }
            }
            if (!$hasDeviceAccess) {
                return 'accessDenied';
            }
        }

        // Check deviceTypeSecret access
        foreach ($this->getUser()->getAccessTags() as $userAccessTag) {
            if ($deviceSecret->getDeviceTypeSecret()->getAccessTags()->contains($userAccessTag)) {
                return null;
            }
        }

        return 'accessDenied';
    }

    // GET Deny key should not be used
    public function getDeny(DeviceSecret $deviceSecret): ?string
    {
        return 'accessDenied';
    }

    // DELETE should not be used
    public function deleteDeny(DeviceSecret $deviceSecret): ?string
    {
        return 'accessDenied';
    }

    public function showDeny(DeviceSecret $deviceSecret): ?string
    {
        if (!$deviceSecret->getId()) {
            return 'accessDenied';
        }

        return $this->checkUserAccess($deviceSecret);
    }

    public function showVariablesDeny(DeviceSecret $deviceSecret): ?string
    {
        if ($deviceSecret->getId()) {
            return 'accessDenied';
        }

        return $this->checkUserAccess($deviceSecret);
    }

    public function createDeny(DeviceSecret $deviceSecret): ?string
    {
        // Only ROLE_ADMIN and ROLE_SMARTEMS can create/edit/delete deviceSecrets
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if ($deviceSecret->getId()) {
            return 'deviceSecretExists';
        }

        $userAccess = $this->checkUserAccess($deviceSecret);
        if ($userAccess) {
            return $userAccess;
        }

        // Just in case of invalid code
        if (!$deviceSecret->getDeviceTypeSecret()) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getDeviceTypeSecret()->getManualEdit()) {
            return 'accessDenied';
        }

        return null;
    }

    public function editDeny(DeviceSecret $deviceSecret): ?string
    {
        // Only ROLE_ADMIN and ROLE_SMARTEMS can create/edit/delete/forceRenewal deviceSecrets
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getId()) {
            return 'deviceSecretDoesNotExist';
        }

        $userAccess = $this->checkUserAccess($deviceSecret);
        if ($userAccess) {
            return $userAccess;
        }

        // Just in case of invalid code
        if (!$deviceSecret->getDeviceTypeSecret()) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getDeviceTypeSecret()->getManualEdit()) {
            return 'accessDenied';
        }

        return null;
    }

    public function enableForceRenewalDeny(DeviceSecret $deviceSecret): ?string
    {
        // Only ROLE_ADMIN and ROLE_SMARTEMS can create/edit/delete/forceRenewal deviceSecrets
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getId()) {
            return 'deviceSecretDoesNotExist';
        }

        $userAccess = $this->checkUserAccess($deviceSecret);
        if ($userAccess) {
            return $userAccess;
        }

        // Just in case of invalid code
        if (!$deviceSecret->getDeviceTypeSecret()) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getDeviceTypeSecret()->getUseAsVariable()) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getDeviceTypeSecret()->getManualForceRenewal()) {
            return 'accessDenied';
        }

        if (SecretValueBehaviour::NONE === $deviceSecret->getDeviceTypeSecret()->getSecretValueBehaviour()) {
            return 'accessDenied';
        }

        if ($deviceSecret->getForceRenewal()) {
            return 'accessDenied';
        }

        return null;
    }

    public function disableForceRenewalDeny(DeviceSecret $deviceSecret): ?string
    {
        // Only ROLE_ADMIN and ROLE_SMARTEMS can create/edit/delete/forceRenewal deviceSecrets
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getId()) {
            return 'deviceSecretDoesNotExist';
        }

        $userAccess = $this->checkUserAccess($deviceSecret);
        if ($userAccess) {
            return $userAccess;
        }

        // Just in case of invalid code
        if (!$deviceSecret->getDeviceTypeSecret()) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getDeviceTypeSecret()->getUseAsVariable()) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getDeviceTypeSecret()->getManualForceRenewal()) {
            return 'accessDenied';
        }

        if (SecretValueBehaviour::NONE === $deviceSecret->getDeviceTypeSecret()->getSecretValueBehaviour()) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getForceRenewal()) {
            return 'accessDenied';
        }

        return null;
    }

    public function clearDeny(DeviceSecret $deviceSecret): ?string
    {
        // Only ROLE_ADMIN and ROLE_SMARTEMS can create/edit/delete/forceRenewal deviceSecrets
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getId()) {
            return 'deviceSecretDoesNotExist';
        }

        $userAccess = $this->checkUserAccess($deviceSecret);
        if ($userAccess) {
            return $userAccess;
        }

        // Just in case of invalid code
        if (!$deviceSecret->getDeviceTypeSecret()) {
            return 'accessDenied';
        }

        if (!$deviceSecret->getDeviceTypeSecret()->getManualEdit()) {
            return 'accessDenied';
        }

        return null;
    }
}
