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

class ImportFileRowReinstallConfig1Validator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        $deviceType = $protocol->getDeviceType();
        if (!$deviceType) {
            return;
        }

        if ($protocol->getReinstallConfig1() && !$deviceType->getHasConfig1()) {
            $this->context->buildViolation($constraint->messageConfig1Disabled)->atPath('reinstallConfig1')->addViolation();
        }

        if ($protocol->getReinstallConfig1() && $deviceType->getHasAlwaysReinstallConfig1()) {
            $this->context->buildViolation($constraint->messageConfig1Always)->atPath('reinstallConfig1')->addViolation();
        }
    }
}
