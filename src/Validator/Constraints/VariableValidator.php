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

use App\Entity\DeviceVariable;
use App\Entity\ImportFileRowVariable;
use App\Entity\TemplateVersionVariable;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class VariableValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        if ($protocol instanceof TemplateVersionVariable) {
            $origin = $protocol->getTemplateVersion();
        } elseif ($protocol instanceof DeviceVariable) {
            $origin = $protocol->getDevice();
        } elseif ($protocol instanceof ImportFileRowVariable) {
            $origin = $protocol->getRow();
        } else {
            // Cannot use Symfony\Component\Validator\Exception\UnexpectedValueException due to multiple accepted types
            throw new \Exception('VariableValidator only supports '.TemplateVersionVariable::class.', '.DeviceVariable::class.' and '.ImportFileRowVariable::class);
        }

        if (!$origin) {
            return;
        }

        $count = 0;

        foreach ($origin->getVariables() as $variable) {
            if ($variable->getName() == $protocol->getName()) {
                ++$count;
            }
        }

        if ($count > 1) {
            $this->context->buildViolation($constraint->messageVariableNameNotUnique)->atPath('name')->addViolation();
        }
    }
}
