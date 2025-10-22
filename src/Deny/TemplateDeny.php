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
use App\Security\SecurityHelperTrait;
use App\Service\Helper\UserTrait;

class TemplateDeny extends AbstractApiDuplicateObjectDeny
{
    use SecurityHelperTrait;
    use UserTrait;
    use TemplateDenyHelperTrait;

    public const CREATE_TEMPLATE_VERSION = 'createTemplateVersion';

    public function createTemplateVersionDeny(Template $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            if ($this->isAnyDeviceInaccessibleUsingTemplate($object)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }
        }

        return null;
    }

    public function editDeny(Template $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            if ($this->isAnyDeviceInaccessibleUsingTemplate($object)) {
                return 'accessDeniedDeviceOutsideAccessScope';
            }
        }

        return null;
    }

    public function deleteDeny(Template $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $object->getCreatedBy()) {
                return 'accessDeniedTemplateNotOwned';
            }

            foreach ($object->getTemplateVersions() as $templateVersion) {
                if ($this->getUser() != $templateVersion->getCreatedBy()) {
                    return 'accessDeniedTemplateVersionNotOwned';
                }
            }
        }

        if (count($object->getDevices()) > 0) {
            return 'usedByDevice';
        }

        return null;
    }
}
