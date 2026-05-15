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
use phpseclib3\File\X509;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class X509CrlValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value) {
            return;
        }

        $x509 = new X509();

        // Load the CRL
        $crlData = $x509->loadCRL($value);
        if (!$crlData) {
            $this->context->buildViolation($constraint->messageInvalidCrl)->addViolation();

            return;
        }

        $nextUpdate = Arr::get($crlData, 'tbsCertList.nextUpdate.utcTime');

        if ($nextUpdate && strtotime($nextUpdate) < time()) {
            $this->context->buildViolation($constraint->messageExpiredCrl)->addViolation();
        }
    }
}
