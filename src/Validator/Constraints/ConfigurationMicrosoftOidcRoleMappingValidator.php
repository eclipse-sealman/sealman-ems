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

use App\Enum\MicrosoftOidcRole;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConfigurationMicrosoftOidcRoleMappingValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        $this->validateRoleVpnEndpointDevices($protocol, $constraint);
        $this->validateAccessTags($protocol, $constraint);
        $this->validateRoleNameUniqueness($protocol, $constraint);
    }

    protected function validateRoleVpnEndpointDevices($protocol, Constraint $constraint): void
    {
        $supportedRoles = [
            MicrosoftOidcRole::VPN,
            MicrosoftOidcRole::SMARTEMS_VPN,
        ];
        $roleVpnEndpointDevicesSupported = in_array($protocol->getMicrosoftOidcRole(), $supportedRoles);

        if (!$roleVpnEndpointDevicesSupported && $protocol->getRoleVpnEndpointDevices()) {
            $this->context->buildViolation($constraint->messageRoleVpnEndpointDevicesNotSupported)->atPath('roleVpnEndpointDevices')->addViolation();
        }
    }

    protected function validateAccessTags($protocol, Constraint $constraint): void
    {
        $supportedRoles = [
            MicrosoftOidcRole::SMARTEMS,
            MicrosoftOidcRole::VPN,
            MicrosoftOidcRole::SMARTEMS_VPN,
        ];
        $accessTagsSupported = in_array($protocol->getMicrosoftOidcRole(), $supportedRoles);
        $accessTagCount = $protocol->getAccessTags()->count();

        if (!$accessTagsSupported && 0 !== $accessTagCount) {
            $this->context->buildViolation($constraint->messageAccessTagsNotSupported)->atPath('accessTags')->addViolation();
        }

        if ($accessTagsSupported && 0 === $accessTagCount) {
            $this->context->buildViolation($constraint->messageOneAccessTagRequired)->atPath('accessTags')->addViolation();
        }
    }

    protected function validateRoleNameUniqueness($protocol, Constraint $constraint): void
    {
        $roleNameCount = 0;

        foreach ($protocol->getConfiguration()->getMicrosoftOidcRoleMappings() as $microsoftOidcRoleMapping) {
            if ($microsoftOidcRoleMapping->getRoleName() == $protocol->getRoleName()) {
                ++$roleNameCount;
            }
        }

        if ($roleNameCount > 1) {
            $this->context->buildViolation($constraint->messageRoleNameNotUnique)->atPath('roleName')->addViolation();
        }
    }
}
