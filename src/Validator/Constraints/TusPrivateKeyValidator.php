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

use App\Service\UploadManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TusPrivateKeyValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value) {
            return;
        }

        $tusFile = UploadManager::getTusFile($value);
        $filepath = $tusFile['file_path'] ?? null;

        if (!$filepath) {
            $this->context->buildViolation($constraint->messageFileMissing)->addViolation();

            return;
        }

        try {
            $fileContent = \file_get_contents($filepath);
        } catch (\Throwable $e) {
            $this->context->buildViolation($constraint->messageFileMissing)->addViolation();

            return;
        }

        if (false === $fileContent) {
            $this->context->buildViolation($constraint->messageFileMissing)->addViolation();

            return;
        }

        if (!$fileContent) {
            $this->context->buildViolation($constraint->messageFileEmpty)->addViolation();

            return;
        }

        try {
            $privateKey = \openssl_pkey_get_private($fileContent);
        } catch (\Throwable $e) {
            $this->context->buildViolation($constraint->messageInvalid)->addViolation();

            return;
        }

        if (false === $privateKey) {
            $this->context->buildViolation($constraint->messageInvalid)->addViolation();
        }
    }
}
