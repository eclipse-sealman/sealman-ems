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

use Carve\ApiBundle\Helper\Arr;
use Symfony\Component\Validator\Constraint;

class X509CaValidator extends X509Validator
{
    public function validate($value, Constraint $constraint): void
    {
        // Common validation logic for X.509 certificates
        $certificateArray = $this->getNotExpiredCertificateArrayOrBuildViolation($value, $constraint);

        if (false === $certificateArray) {
            return;
        }

        // Check for CA capability
        $hasCaCapability = str_contains(Arr::get($certificateArray, 'extensions.basicConstraints', ''), 'CA:TRUE');

        if (!$hasCaCapability) {
            $this->context->buildViolation($constraint->messageNoCaCapability)->addViolation();
        }
    }
}
