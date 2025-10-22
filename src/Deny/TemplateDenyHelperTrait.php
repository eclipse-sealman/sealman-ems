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

use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Entity\Traits\TemplateComponentInterface;
use App\Enum\TemplateVersionType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\UserTrait;

trait TemplateDenyHelperTrait
{
    use AuthorizationCheckerTrait;
    use UserTrait;
    use SecurityHelperTrait;

    public function isAnyProductionTemplateVersionUsingTemplateComponent(TemplateComponentInterface $object): bool
    {
        foreach ($object->getTemplates1() as $templateVersion) {
            if (TemplateVersionType::PRODUCTION == $templateVersion->getType()) {
                return true;
            }
        }

        foreach ($object->getTemplates2() as $templateVersion) {
            if (TemplateVersionType::PRODUCTION == $templateVersion->getType()) {
                return true;
            }
        }

        foreach ($object->getTemplates3() as $templateVersion) {
            if (TemplateVersionType::PRODUCTION == $templateVersion->getType()) {
                return true;
            }
        }

        return false;
    }

    public function isAnyDeviceInaccessibleUsingTemplateComponent(TemplateComponentInterface $object): bool
    {
        if ($this->isAllDevicesGranted()) {
            return false;
        }

        foreach ($object->getTemplates1() as $templateVersion) {
            if ($this->isAnyDeviceInaccessibleUsingTemplateVersion($templateVersion)) {
                return true;
            }
        }

        foreach ($object->getTemplates2() as $templateVersion) {
            if ($this->isAnyDeviceInaccessibleUsingTemplateVersion($templateVersion)) {
                return true;
            }
        }

        foreach ($object->getTemplates3() as $templateVersion) {
            if ($this->isAnyDeviceInaccessibleUsingTemplateVersion($templateVersion)) {
                return true;
            }
        }

        return false;
    }

    public function isAnyDeviceAccessibleUsingTemplateVersion(TemplateVersion $templateVersion): bool
    {
        if ($templateVersion->getTemplate()) {
            return $this->isAnyDeviceAccessibleUsingTemplate($templateVersion->getTemplate());
        }

        return false;
    }

    public function isAnyDeviceInaccessibleUsingTemplateVersion(TemplateVersion $templateVersion): bool
    {
        if ($templateVersion->getTemplate()) {
            return $this->isAnyDeviceInaccessibleUsingTemplate($templateVersion->getTemplate());
        }

        return false;
    }

    public function isAnyDeviceInaccessibleUsingTemplate(Template $template): bool
    {
        foreach ($template->getDevices() as $device) {
            $deviceAccess = false;
            foreach ($device->getAccessTags() as $accessTag) {
                if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                    $deviceAccess = true;
                    break;
                }
            }
            if (!$deviceAccess) {
                return true;
            }
        }

        return false;
    }

    public function isAnyDeviceAccessibleUsingTemplate(Template $template): bool
    {
        foreach ($template->getDevices() as $device) {
            foreach ($device->getAccessTags() as $accessTag) {
                if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                    return true;
                }
            }
        }

        return false;
    }
}
