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

use App\Service\Helper\UserTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserValidator extends ConstraintValidator
{
    use UserTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        if ($protocol == $this->getUser()) {
            if (!$protocol->getIsEnabled()) {
                $this->context->buildViolation($constraint->messageCannotDisableYourself)->atPath('enabled')->addViolation();
            }

            if (!$protocol->getRoleAdmin()) {
                $this->context->buildViolation($constraint->messageCannotDemoteYourself)->atPath('enabled')->addViolation();
            }
        }

        if (!$protocol->getRoleAdmin() && !$protocol->getRoleSmartems() && !$protocol->getRoleVpn()) {
            $this->context->buildViolation($constraint->messageOneRoleRequired)->atPath('roleAdmin')->addViolation();
            $this->context->buildViolation($constraint->messageOneRoleRequired)->atPath('roleSmartems')->addViolation();
            $this->context->buildViolation($constraint->messageOneRoleRequired)->atPath('roleVpn')->addViolation();
        }

        if ($protocol->getRoleSmartems() || $protocol->getRoleVpn()) {
            if (0 == $protocol->getAccessTags()->count()) {
                $this->context->buildViolation($constraint->messageOneAccessTagRequired)->atPath('accessTags')->addViolation();
            }
        }

        // App\Form\UserEditType removes "roleVpnEndpointDevices" field when "roleVpn" is false. Validate anyway in case of future code changes.
        if ($protocol->getRoleVpnEndpointDevices() && !$protocol->getRoleVpn()) {
            $this->context->buildViolation($constraint->messageRoleVpnEndpointDevicesRequiresRoleVpn)->atPath('roleVpnEndpointDevices')->addViolation();
        }

        if (!$protocol->getEnabled() && $protocol->getEnabledExpireAt()) {
            $this->context->buildViolation($constraint->messageDisabledExpireAtNotNull)->atPath('enabled')->addViolation();
        }

        // Not validating roleVPN against VPN license, because field is not available in form if no VPN license. This validation would prevent handling SMA+VPN users while VPN license was downgraded
    }
}
