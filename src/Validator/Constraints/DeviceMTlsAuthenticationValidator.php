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

use App\Enum\CrlType;
use phpseclib3\File\X509;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DeviceMTlsAuthenticationValidator extends ConstraintValidator
{
    public function validate($entity, Constraint $constraint): void
    {
        // If crlType === "pem", then certificateCrl is required
        if (CrlType::PEM === $entity->getCrlType()) {
            if (!$entity->getCertificateCrl()) {
                $this->context->buildViolation($constraint->messageCrlRequiredForPem)
                    ->atPath('certificateCrl')
                    ->addViolation();

                return;
            }
            // Checking if CRL is signed by CA
            $x509 = new X509();
            $x509->loadCRL($entity->getCertificateCrl());
            $x509->loadCA($entity->getCertificateCa());

            // Validating if CRL is signed by CA
            if (true !== $x509->validateSignature()) {
                $this->context->buildViolation($constraint->messageCrlNotMatchingCa)
                    ->atPath('certificateCrl')
                    ->addViolation();
            }
        }

        // If crlType === "url", then certificateCrlUrl is required
        if (CrlType::URL === $entity->getCrlType()) {
            if (!$entity->getCertificateCrlUrl()) {
                $this->context->buildViolation($constraint->messageCrlUrlRequiredForUrl)
                    ->atPath('certificateCrlUrl')
                    ->addViolation();
            }
        }
    }
}
