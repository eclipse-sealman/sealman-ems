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

use App\Entity\DeviceType;
use App\Service\Helper\DeviceCommunicationFactoryTrait;

class DeviceTypeDeny extends AbstractApiDuplicateObjectDeny
{
    use DeviceCommunicationFactoryTrait;

    public const LIMITED_EDIT = 'limitedEdit';
    public const ENABLE = 'enable';
    public const DISABLE = 'disable';

    public function enableDeny(DeviceType $deviceType): ?string
    {
        if ($deviceType->getEnabled()) {
            return 'alreadyEnabled';
        }

        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
        if (!$communicationProcedure->isDeviceTypeValid()) {
            return 'cannotEnable';
        }

        return null;
    }

    public function disableDeny(DeviceType $deviceType): ?string
    {
        if (!$deviceType->getEnabled()) {
            return 'alreadyDisabled';
        }

        return null;
    }

    public function deleteDeny(DeviceType $deviceType): ?string
    {
        return $this->getUsedDeny($deviceType) ? 'delete.'.$this->getUsedDeny($deviceType) : null;
    }

    public function editDeny(DeviceType $deviceType): ?string
    {
        return $this->getUsedDeny($deviceType) ? 'edit.'.$this->getUsedDeny($deviceType) : null;
    }

    public function limitedEditDeny(DeviceType $deviceType): ?string
    {
        return $this->getUsedDeny($deviceType) ? null : 'limitedEdit';
    }

    public function getUsedDeny(DeviceType $deviceType): ?string
    {
        if ($deviceType->getDevices()->count() > 0) {
            return 'usedByDevice';
        }

        if ($deviceType->getTemplateVersions()->count() > 0) {
            return 'usedByTemplate';
        }

        if ($deviceType->getTemplates()->count() > 0) {
            return 'usedByTemplate';
        }

        if ($deviceType->getConfigs()->count() > 0) {
            return 'usedByConfig';
        }

        if ($deviceType->getFirmwares()->count() > 0) {
            return 'usedByFirmware';
        }

        if ($deviceType->getDeviceTypeSecrets()->count() > 0) {
            return 'usedByDeviceTypeSecret';
        }

        return null;
    }
}
