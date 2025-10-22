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

namespace App\Validator\Constraints;

use App\Enum\MaintenanceType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaintenanceValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        if (MaintenanceType::BACKUP === $protocol->getType()) {
            if (!$protocol->getBackupDatabase() && !$protocol->getBackupFilestorage()) {
                $this->context->buildViolation($constraint->messageBackupEmpty)->atPath('backupDatabase')->addViolation();
                $this->context->buildViolation($constraint->messageBackupEmpty)->atPath('backupFilestorage')->addViolation();
            }
        }

        if (MaintenanceType::RESTORE === $protocol->getType()) {
            if (!$protocol->getFilepath()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('filepath')->addViolation();
            }

            if (!$protocol->getRestoreDatabase() && !$protocol->getRestoreFilestorage()) {
                $this->context->buildViolation($constraint->messageRestoreEmpty)->atPath('restoreDatabase')->addViolation();
                $this->context->buildViolation($constraint->messageRestoreEmpty)->atPath('restoreFilestorage')->addViolation();
            }
        }
    }
}
