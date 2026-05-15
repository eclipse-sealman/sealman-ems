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

use App\Entity\FirmwareHardwareFile;
use App\Enum\SourceType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\UserTrait;

class FirmwareHardwareFileDeny extends AbstractApiDuplicateObjectDeny
{
    use SecurityHelperTrait;
    use TemplateDenyHelperTrait;
    use UserTrait;

    public const CREATE = 'create';
    public const EDIT_SOURCE_UPLOAD = 'editSourceUpload';
    public const EDIT_SOURCE_EXTERNAL_URL = 'editSourceExternalUrl';

    public function createDeny(FirmwareHardwareFile $object): ?string
    {
        $firmware = $object->getFirmware();
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $firmware->getCreatedBy()) {
                return 'accessDeniedNotOwned';
            }

            if ($this->isAnyDeviceInaccessibleUsingTemplateComponent($firmware)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }
        }

        if ($this->isAnyProductionTemplateVersionUsingTemplateComponent($firmware)) {
            return 'accessDeniedUsedByProductionTemplateVersion';
        }

        return null;
    }

    public function editSourceUploadDeny(FirmwareHardwareFile $object): ?string
    {
        return 'sourceTypeUploadEditNotSupported';
    }

    public function editSourceExternalUrlDeny(FirmwareHardwareFile $object): ?string
    {
        if (SourceType::EXTERNAL_URL !== $object->getSourceType()) {
            return 'notSourceTypeExternalUrl';
        }

        $firmware = $object->getFirmware();
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $firmware->getCreatedBy()) {
                return 'accessDeniedNotOwned';
            }

            if ($this->isAnyDeviceInaccessibleUsingTemplateComponent($firmware)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }
        }

        if ($this->isAnyProductionTemplateVersionUsingTemplateComponent($firmware)) {
            return 'accessDeniedUsedByProductionTemplateVersion';
        }

        return null;
    }

    public function deleteDeny(FirmwareHardwareFile $object): ?string
    {
        $firmware = $object->getFirmware();
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $firmware->getCreatedBy()) {
                return 'accessDeniedNotOwned';
            }
        }

        if (count($firmware->getTemplates1()) > 0 || count($firmware->getTemplates2()) > 0 || count($firmware->getTemplates3()) > 0) {
            return 'usedByTemplate';
        }

        return null;
    }
}
