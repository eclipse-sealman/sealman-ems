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

use App\Enum\SecretValueBehaviour;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DeviceTypeSecretValidator extends ConstraintValidator
{
    public function validate($deviceTypeSecret, Constraint $constraint): void
    {
        if (!$deviceTypeSecret) {
            return;
        }

        if ($deviceTypeSecret->getUseAsVariable() && SecretValueBehaviour::isRenew($deviceTypeSecret->getSecretValueBehaviour()) && !$deviceTypeSecret->getSecretValueRenewAfterDays()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('secretValueRenewAfterDays')->addViolation();
        }

        if ((!$deviceTypeSecret->getUseAsVariable() || !SecretValueBehaviour::isGenerate($deviceTypeSecret->getSecretValueBehaviour())) && !$deviceTypeSecret->getManualEdit()) {
            $this->context->buildViolation($constraint->messageCreateNotPossible)->atPath('manualEdit')->addViolation();
        }
    }
}
