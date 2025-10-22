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

use App\Entity\ImportFileRow;
use App\Enum\ImportFileRowImportStatus;

class ImportFileRowDeny extends AbstractApiDuplicateObjectDeny
{
    public const VARIABLE_ADD = 'variableAdd';
    public const VARIABLE_DELETE = 'variableDelete';
    public const TEMPLATE_CHANGE = 'templateChange';

    public function templateChangeDeny(ImportFileRow $row): ?string
    {
        $deviceType = $row->getDeviceType();
        if (!$deviceType) {
            return 'deviceTypeMissing';
        }

        if (!$deviceType->getHasTemplates()) {
            return 'templatesDisabled';
        }

        if (ImportFileRowImportStatus::PENDING !== $row->getImportStatus()) {
            return 'statusNotPending';
        }

        return null;
    }

    public function variableAddDeny(ImportFileRow $row): ?string
    {
        $deviceType = $row->getDeviceType();
        if (!$deviceType) {
            return 'deviceTypeMissing';
        }

        if (!$deviceType->getHasVariables()) {
            return 'variablesDisabled';
        }

        if (ImportFileRowImportStatus::PENDING !== $row->getImportStatus()) {
            return 'statusNotPending';
        }

        return null;
    }

    public function variableDeleteDeny(ImportFileRow $row): ?string
    {
        $deviceType = $row->getDeviceType();
        if (!$deviceType) {
            return 'deviceTypeMissing';
        }

        if (!$deviceType->getHasVariables()) {
            return 'variablesDisabled';
        }

        if (ImportFileRowImportStatus::PENDING !== $row->getImportStatus()) {
            return 'statusNotPending';
        }

        return null;
    }

    public function editDeny(ImportFileRow $row): ?string
    {
        if (ImportFileRowImportStatus::PENDING !== $row->getImportStatus()) {
            return 'statusNotPending';
        }

        return null;
    }
}
