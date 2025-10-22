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

use App\Entity\DeviceMasquerade;
use App\Entity\TemplateVersionMasquerade;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MasqueradeValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        if ($protocol instanceof TemplateVersionMasquerade) {
            $origin = $protocol->getTemplateVersion();
        } elseif ($protocol instanceof DeviceMasquerade) {
            $origin = $protocol->getDevice();
        } else {
            // Cannot use Symfony\Component\Validator\Exception\UnexpectedValueException due to two accepted types
            throw new \Exception('MasqueradeValidator only supports '.TemplateVersionMasquerade::class.' and '.DeviceMasquerade::class);
        }

        if (!$origin) {
            return;
        }

        $count = 0;

        foreach ($origin->getMasquerades() as $masquerade) {
            if ($masquerade->getSubnet() == $protocol->getSubnet()) {
                ++$count;
            }
        }

        if ($count > 1) {
            $this->context->buildViolation($constraint->messageSubnetNotUnique)->atPath('subnet')->addViolation();
        }
    }
}
