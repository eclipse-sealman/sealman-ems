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

use App\Entity\Firmware;
use App\Enum\SourceType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\UserTrait;

class FirmwareDeny extends AbstractApiDuplicateObjectDeny
{
    use AuthorizationCheckerTrait;
    use UserTrait;
    use TemplateDenyHelperTrait;
    use SecurityHelperTrait;

    public const EDIT_SOURCE_UPLOAD = 'editSourceUpload';
    public const EDIT_SOURCE_EXTERNAL_URL = 'editSourceExternalUrl';
    public const EDIT_HARDWARE_FILES = 'editHardwareFiles';
    public const HARDWARE_FILES_LIST = 'hardwareFilesList';
    public const HARDWARE_FILES_CREATE = 'hardwareFilesCreate';
    public const EDIT_REQUIRED_FIRMWARE = 'editRequiredFirmware';
    public const SHOW_UPDATE_PATH = 'showUpdatePath';

    public function hardwareFilesListDeny(Firmware $object): ?string
    {
        if (!$object->getEnableHardwareFiles()) {
            return 'hardwareFilesDisabled';
        }

        return null;
    }

    public function hardwareFilesCreateDeny(Firmware $object): ?string
    {
        if (!$object->getEnableHardwareFiles()) {
            return 'hardwareFilesDisabled';
        }

        // We assume that from security perspective creating a hardware file is the same as editing firmware
        $editDeny = $this->editDeny($object);
        if (null !== $editDeny) {
            return $editDeny;
        }

        return null;
    }

    public function editDeny(Firmware $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $object->getCreatedBy()) {
                return 'accessDeniedNotOwned';
            }

            if ($this->isAnyDeviceInaccessibleUsingTemplateComponent($object)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }
        }

        if ($this->isAnyProductionTemplateVersionUsingTemplateComponent($object)) {
            return 'accessDeniedUsedByProductionTemplateVersion';
        }

        return null;
    }

    public function editHardwareFilesDeny(Firmware $object): ?string
    {
        if (!$object->getEnableHardwareFiles()) {
            return 'hardwareFilesDisabled';
        }

        return null;
    }

    public function editRequiredFirmwareDeny(Firmware $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $object->getCreatedBy()) {
                return 'accessDeniedNotOwned';
            }
        }

        if (count($object->getTemplates1()) > 0 || count($object->getTemplates2()) > 0 || count($object->getTemplates3()) > 0) {
            return 'usedByTemplate';
        }

        $count = $this->getRepository(Firmware::class)->count(['requiredFirmware' => $object]);
        if ($count > 0) {
            return 'usedAsRequiredFirmware';
        }

        return null;
    }

    public function editSourceUploadDeny(Firmware $object): ?string
    {
        if (SourceType::UPLOAD !== $object->getSourceType()) {
            return 'onlySourceTypeUpload';
        }

        if ($object->getEnableHardwareFiles()) {
            return 'hardwareFilesEnabled';
        }

        $editDeny = $this->editDeny($object);
        if (null !== $editDeny) {
            return $editDeny;
        }

        return null;
    }

    public function editSourceExternalUrlDeny(Firmware $object): ?string
    {
        if (SourceType::EXTERNAL_URL !== $object->getSourceType()) {
            return 'onlySourceTypeExternalUrl';
        }

        if ($object->getEnableHardwareFiles()) {
            return 'hardwareFilesEnabled';
        }

        $editDeny = $this->editDeny($object);
        if (null !== $editDeny) {
            return $editDeny;
        }

        return null;
    }

    public function showUpdatePathDeny(Firmware $object): ?string
    {
        if (!$object->getRequiredFirmware()) {
            return 'accessDeniedNotOwned';
        }

        return null;
    }

    public function deleteDeny(Firmware $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $object->getCreatedBy()) {
                return 'accessDeniedNotOwned';
            }
        }

        if (count($object->getTemplates1()) > 0 || count($object->getTemplates2()) > 0 || count($object->getTemplates3()) > 0) {
            return 'usedByTemplate';
        }

        $count = $this->getRepository(Firmware::class)->count(['requiredFirmware' => $object]);
        if ($count > 0) {
            return 'usedAsRequiredFirmware';
        }

        return null;
    }
}
