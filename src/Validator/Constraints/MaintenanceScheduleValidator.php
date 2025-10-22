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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaintenanceScheduleValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        if (!$protocol->getBackupDatabase() && !$protocol->getBackupFilestorage()) {
            $this->context->buildViolation($constraint->messageBackupEmpty)->atPath('backupDatabase')->addViolation();
            $this->context->buildViolation($constraint->messageBackupEmpty)->atPath('backupFilestorage')->addViolation();
        }

        if (0 === $protocol->getDayOfMonth()) {
            $this->context->buildViolation($constraint->messageDayOfMonthZero)->atPath('dayOfMonth')->addViolation();
        }
    }
}
