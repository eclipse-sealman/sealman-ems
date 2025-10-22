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

use App\Service\Helper\VpnAddressManagerTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\CidrValidator;

class SubnetValidator extends CidrValidator
{
    use VpnAddressManagerTrait;

    public function validate($value, Constraint $constraint): void
    {
        parent::validate($value, $constraint);

        if ($value && !$this->hasViolation()) {
            // Validate further only when $value is not empty and there are no violations for this $propertyPath
            if (!$this->vpnAddressManager->isSubnetAddressValid($value)) {
                $this->context->buildViolation($constraint->messageSubnet, ['{{ subnet }}' => $value])->addViolation();
            }
        }
    }

    protected function hasViolation(): bool
    {
        $propertyPath = $this->context->getPropertyPath();

        foreach ($this->context->getViolations() as $violation) {
            if ($violation->getPropertyPath() === $propertyPath) {
                return true;
            }
        }

        return false;
    }
}
