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

use App\Service\Helper\ConfigurationManagerTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RouterCommunicationIdentifierValidator extends ConstraintValidator
{
    use ConfigurationManagerTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        if ($this->configurationManager->isSerialRouterIdentifier()) {
            if (!$protocol->getSerial()) {
                $this->context->buildViolation($constraint->message)->atPath('Serial')->addViolation();
            }
        }

        if ($this->configurationManager->isImsiRouterIdentifier()) {
            if (!$protocol->getIMSI()) {
                if (!$protocol->getSerial()) {
                    $this->context->buildViolation($constraint->message)->atPath('Serial')->addViolation();
                    $this->context->buildViolation($constraint->message)->atPath('IMSI')->addViolation();
                }
            }
        }
    }
}
