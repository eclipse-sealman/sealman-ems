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
use Symfony\Component\Validator\Constraints\IpValidator;

class IpRangeValidator extends IpValidator
{
    use VpnAddressManagerTrait;

    public function validate($value, Constraint $constraint): void
    {
        $rangeParts = explode('-', $value);

        // Check if range consists of 'startIp' 'minus sign' 'endIp' e.g. 192.168.154.0-192.168.154.100
        if (2 != count($rangeParts)) {
            $this->context->buildViolation($constraint->messageInvalidIpRange, ['{{ range }}' => $value])->addViolation();
        }

        // Check if 'startIp' is valid ipv4
        if ($value && !$this->hasViolation()) {
            parent::validate($rangeParts[0], $constraint);
        }

        // Check if 'endIp' is valid ipv4
        if ($value && !$this->hasViolation()) {
            parent::validate($rangeParts[1], $constraint);
        }

        // Check if 'startIp' is less or equal to 'endIp'
        if ($value && !$this->hasViolation()) {
            if (ip2long($rangeParts[0]) > ip2long($rangeParts[1])) {
                $this->context->buildViolation($constraint->messageInvalidIpRange, ['{{ range }}' => $value])->addViolation();
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
