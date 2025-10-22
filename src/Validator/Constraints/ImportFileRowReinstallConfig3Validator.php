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

class ImportFileRowReinstallConfig3Validator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        $deviceType = $protocol->getDeviceType();
        if (!$deviceType) {
            return;
        }

        if ($protocol->getReinstallConfig3() && !$deviceType->getHasConfig3()) {
            $this->context->buildViolation($constraint->messageConfig3Disabled)->atPath('reinstallConfig3')->addViolation();
        }

        if ($protocol->getReinstallConfig3() && $deviceType->getHasAlwaysReinstallConfig3()) {
            $this->context->buildViolation($constraint->messageConfig3Always)->atPath('reinstallConfig3')->addViolation();
        }
    }
}
