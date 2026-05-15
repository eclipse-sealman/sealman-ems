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

use App\Enum\SourceType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FirmwareSourceTypeValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        $sourceType = $protocol->getSourceType();
        if (!$sourceType) {
            return;
        }

        if (SourceType::UPLOAD === $sourceType) {
            if (!$protocol->getFilepath()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('filepath')->addViolation();
            }
        }

        if (SourceType::EXTERNAL_URL === $sourceType) {
            if (!$protocol->getMd5()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('md5')->addViolation();
            }

            if (!$protocol->getExternalUrl()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('externalUrl')->addViolation();
            }
        }
    }
}
