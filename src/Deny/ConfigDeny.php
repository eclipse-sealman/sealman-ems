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

use App\Entity\Config;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\UserTrait;

class ConfigDeny extends AbstractApiDuplicateObjectDeny
{
    use AuthorizationCheckerTrait;
    use UserTrait;
    use TemplateDenyHelperTrait;
    use SecurityHelperTrait;

    public function editDeny(Config $object): ?string
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

    public function deleteDeny(Config $object): ?string
    {
        if (!$this->isAllDevicesGranted()) {
            if ($this->getUser() != $object->getCreatedBy()) {
                return 'accessDeniedNotOwned';
            }
        }

        if (count($object->getTemplates1()) > 0 || count($object->getTemplates2()) > 0 || count($object->getTemplates3()) > 0) {
            return 'usedByTemplate';
        }

        return null;
    }
}
