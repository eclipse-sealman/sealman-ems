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

use App\Entity\ImportFile;
use App\Enum\ImportFileStatus;
use App\Service\Helper\ImportDeviceManagerTrait;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;

class ImportFileDeny extends AbstractApiObjectDeny
{
    use ImportDeviceManagerTrait;

    public const IMPORT_NEXT_ROW = 'importNextRow';

    public function importNextRowDeny(ImportFile $importFile): ?string
    {
        if (ImportFileStatus::FINISHED === $importFile->getStatus()) {
            return 'finished';
        }

        if (!$this->importDeviceManager->getNextImportFileRow($importFile)) {
            return 'finished';
        }

        return null;
    }
}
