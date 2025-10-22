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

class EndpointDeviceLockValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        // Skip new endpoint devices
        if (!$protocol->getId()) {
            return;
        }

        // Ensure existing endpoint devices has the lock set
        $lock = $protocol->getLock();
        if (null === $lock) {
            throw new \Exception(EndpointDeviceLockValidator::class.' requires $lock to be set');
        }

        if ($lock->getLockVirtualIpHostPart() && $lock->getVirtualIpHostPart() !== $protocol->getVirtualIpHostPart()) {
            $this->context->buildViolation($constraint->messageVirtualIpHostPartLocked)->atPath('virtualIpHostPart')->addViolation();
        }
    }
}
