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

use App\Enum\RadiusUserRole;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConfigurationRadiusWelotecGroupMappingValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        $this->validateRoleVpnEndpointDevices($protocol, $constraint);
        $this->validateNameUniqueness($protocol, $constraint);
    }

    protected function validateRoleVpnEndpointDevices($protocol, Constraint $constraint): void
    {
        $supportedRoles = [
            RadiusUserRole::VPN,
            RadiusUserRole::SMARTEMS_VPN,
        ];
        $roleVpnEndpointDevicesSupported = in_array($protocol->getRadiusUserRole(), $supportedRoles);

        if (!$roleVpnEndpointDevicesSupported && $protocol->getRoleVpnEndpointDevices()) {
            $this->context->buildViolation($constraint->messageRoleVpnEndpointDevicesNotSupported)->atPath('roleVpnEndpointDevices')->addViolation();
        }
    }

    protected function validateNameUniqueness($protocol, Constraint $constraint): void
    {
        $groupNameCount = 0;

        foreach ($protocol->getConfiguration()->getRadiusWelotecGroupMappings() as $radiusWelotecGroupMapping) {
            if ($radiusWelotecGroupMapping->getName() == $protocol->getName()) {
                ++$groupNameCount;
            }
        }

        if ($groupNameCount > 1) {
            $this->context->buildViolation($constraint->messageGroupNameNotUnique)->atPath('name')->addViolation();
        }
    }
}
