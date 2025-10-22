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

use App\Entity\Maintenance;
use App\Enum\MaintenanceStatus;
use App\Enum\MaintenanceType;

class MaintenanceDeny extends AbstractApiDuplicateObjectDeny
{
    public const DOWNLOAD = 'download';

    public function downloadDeny(Maintenance $object): ?string
    {
        if (MaintenanceStatus::SUCCESS !== $object->getStatus()) {
            return 'onlyStatusSuccess';
        }

        if (MaintenanceType::BACKUP !== $object->getType() && MaintenanceType::BACKUP_FOR_UPDATE !== $object->getType()) {
            return 'onlyTypeBackupOrBackupForUpdate';
        }

        return null;
    }
}
