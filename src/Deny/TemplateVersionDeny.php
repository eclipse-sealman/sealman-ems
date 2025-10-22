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

use App\Entity\TemplateVersion;
use App\Enum\TemplateVersionType;

class TemplateVersionDeny extends AbstractApiDuplicateObjectDeny
{
    use TemplateDenyHelperTrait;

    public const SELECT_STAGING = 'selectStaging';
    public const SELECT_PRODUCTION = 'selectProduction';
    public const DETACH_STAGING = 'detachStaging';
    public const DETACH_PRODUCTION = 'detachProduction';

    public function selectStagingDeny(TemplateVersion $object): ?string
    {
        $template = $object->getTemplate();
        if ($template->getStagingTemplate() === $object) {
            return 'alreadySelectedStaging';
        }

        if (TemplateVersionType::STAGING !== $object->getType()) {
            return 'selectStagingNotTypeStaging';
        }

        if (!$this->isAllDevicesGranted()) {
            if ($this->isAnyDeviceInaccessibleUsingTemplate($template)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }

            if ($this->getUser() != $object->getCreatedBy() && $template->getStagingTemplate() !== $object && $template->getProductionTemplate() !== $object) {
                return 'accessDeniedNotOwned';
            }
        }

        return null;
    }

    public function selectProductionDeny(TemplateVersion $object): ?string
    {
        $template = $object->getTemplate();
        if ($template->getProductionTemplate() === $object) {
            return 'alreadySelectedProduction';
        }

        $isSelectedStaging = TemplateVersionType::STAGING === $object->getType() && $template->getStagingTemplate() === $object ? true : false;
        if (!$isSelectedStaging && TemplateVersionType::PRODUCTION !== $object->getType()) {
            return 'notSelectedStagingAndNotTypeProduction';
        }

        if (!$this->isAllDevicesGranted()) {
            if ($this->isAnyDeviceInaccessibleUsingTemplate($template)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }

            if ($this->getUser() != $object->getCreatedBy() && $template->getStagingTemplate() !== $object && $template->getProductionTemplate() !== $object) {
                return 'accessDeniedNotOwned';
            }
        }

        return null;
    }

    public function detachStagingDeny(TemplateVersion $object): ?string
    {
        $template = $object->getTemplate();
        if ($template->getStagingTemplate() !== $object) {
            return 'notSelectedStaging';
        }

        if (!$this->isAllDevicesGranted()) {
            if ($this->isAnyDeviceInaccessibleUsingTemplate($template)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }
        }

        return null;
    }

    public function detachProductionDeny(TemplateVersion $object): ?string
    {
        $template = $object->getTemplate();
        if ($template->getProductionTemplate() !== $object) {
            return 'notSelectedProduction';
        }

        if (!$this->isAllDevicesGranted()) {
            if ($this->isAnyDeviceInaccessibleUsingTemplate($template)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }
        }

        return null;
    }

    public function duplicateDeny(TemplateVersion $object): ?string
    {
        // All templateVersion you have access can be duplicated
        return null;
    }

    public function editDeny(TemplateVersion $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            $template = $object->getTemplate();
            // Check if template (of this TemplateVersion) is editable
            if ($this->isAnyDeviceInaccessibleUsingTemplateVersion($object)) {
                // Template not editable - user can only edit non interfering templateVersion (not used) and owned
                if ($this->getUser() != $object->getCreatedBy() || $template->getStagingTemplate() == $object || $template->getProductionTemplate() == $object) {
                    return 'accessDeniedDeviceOutsideAccessScope';
                }
            } else {
                // Template editable - user has full access to edit
                if ($this->getUser() != $object->getCreatedBy() && $template->getStagingTemplate() !== $object && $template->getProductionTemplate() !== $object) {
                    return 'accessDeniedNotOwned';
                }
            }
        }

        if (TemplateVersionType::PRODUCTION === $object->getType()) {
            return 'productionEditDisabled';
        }

        return null;
    }

    public function deleteDeny(TemplateVersion $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $object->getCreatedBy()) {
                return 'accessDeniedNotOwned';
            }
        }

        $template = $object->getTemplate();
        if ($template->getProductionTemplate() === $object) {
            return 'selectedProduction';
        }

        if ($template->getStagingTemplate() === $object) {
            return 'selectedStaging';
        }

        return null;
    }
}
