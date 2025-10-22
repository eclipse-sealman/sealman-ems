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

class DeviceLockValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        // Skip new devices
        if (!$protocol->getId()) {
            return;
        }

        // Ensure existing devices has the lock set
        $lock = $protocol->getLock();
        if (null === $lock) {
            throw new \Exception(DeviceLockValidator::class.' requires $lock to be set');
        }

        if ($lock->getLockVirtualSubnetCidr() && $lock->getVirtualSubnetCidr() !== $protocol->getVirtualSubnetCidr()) {
            $this->context->buildViolation($constraint->messageVirtualSubnetCidrLocked)->atPath('virtualSubnetCidr')->addViolation();
        }

        $lockedEndpointDeviceIds = $lock->getLockedEndpointDeviceIds();
        if (count($lockedEndpointDeviceIds) > 0) {
            $endpointDeviceIds = [];

            foreach ($protocol->getEndpointDevices() as $endpointDevice) {
                if ($endpointDevice->getId()) {
                    $endpointDeviceIds[] = $endpointDevice->getId();
                }
            }

            $idsDiff = array_diff($lockedEndpointDeviceIds, $endpointDeviceIds);
            // At least one IDs from locked endpoint devices does not exist
            if (count($idsDiff) > 0) {
                $this->context->buildViolation($constraint->messageEndpointDeviceLocked)->atPath('endpointDevices')->addViolation();
            }
        }
    }
}
