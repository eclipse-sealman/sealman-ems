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

class X509Validator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        // Common validation logic for X.509 certificates
        $this->getNotExpiredCertificateArrayOrBuildViolation($value, $constraint);
    }

    public function getNotExpiredCertificateArrayOrBuildViolation($value, Constraint $constraint): false|array
    {
        $certificateArray = $this->getCertificateArray($value, $constraint);

        if (false === $certificateArray) {
            return false;
        }

        $validToTimestamp = $certificateArray['validTo_time_t'];
        if (time() > $validToTimestamp) {
            $this->context->buildViolation($constraint->messageExpiredX509)->addViolation();

            return false;
        }

        return $certificateArray;
    }

    public function getCertificateArray($value, Constraint $constraint): false|array
    {
        if (!$value) {
            return false;
        }

        try {
            $certificateArray = \openssl_x509_parse($value);
        } catch (\Throwable $e) {
            $this->context->buildViolation($constraint->messageInvalidX509)->addViolation();

            return false;
        }

        if (false === $certificateArray) {
            $this->context->buildViolation($constraint->messageInvalidX509)->addViolation();
        }

        return $certificateArray;
    }
}
