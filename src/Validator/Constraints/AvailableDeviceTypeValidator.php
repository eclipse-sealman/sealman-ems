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

use App\Entity\DeviceType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AvailableDeviceTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value) {
            return;
        }

        if (!$value instanceof DeviceType) {
            return;
        }

        if (false === $value->getEnabled()) {
            $this->context->buildViolation($constraint->messageDisabled)->addViolation();

            return;
        }

        if (false === $value->getIsAvailable()) {
            $this->context->buildViolation($constraint->messageNotAvailable)->addViolation();

            return;
        }
    }
}
