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

use App\Entity\DeviceType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DeviceTemplateApplyValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        $template = $protocol->getTemplate();

        if (!$template) {
            if ($protocol->getApplyDeviceDescription()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('applyDeviceDescription')->addViolation();
            }

            if ($protocol->getApplyEndpointDevices()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('applyEndpointDevices')->addViolation();
            }

            if ($protocol->getApplyVariables()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('applyVariables')->addViolation();
            }

            if ($protocol->getApplyMasquerade()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('applyMasquerade')->addViolation();
            }

            if ($protocol->getApplyAccessTags()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('applyAccessTags')->addViolation();
            }

            if ($protocol->getReinstallConfig1()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('reinstallConfig1')->addViolation();
            }

            if ($protocol->getReinstallConfig2()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('reinstallConfig2')->addViolation();
            }

            if ($protocol->getReinstallConfig3()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('reinstallConfig3')->addViolation();
            }

            if ($protocol->getReinstallFirmware1()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('reinstallFirmware1')->addViolation();
            }

            if ($protocol->getReinstallFirmware2()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('reinstallFirmware2')->addViolation();
            }

            if ($protocol->getReinstallFirmware3()) {
                $this->context->buildViolation($constraint->messageTemplateMissingApplyInvalid)->atPath('reinstallFirmware3')->addViolation();
            }

            return;
        }

        $deviceType = $template->getDeviceType();
        if (!$deviceType) {
            return;
        }

        // Device type mismatch is validated in Controller so 409 error can be thrown
        // Lack of template version is also validated in Controller so 409 error can be thrown

        if (!$deviceType->getIsMasqueradeAvailable() && $protocol->getApplyMasquerade()) {
            $this->context->buildViolation($constraint->messageMasqueradeDisabled)->atPath('applyMasquerade')->addViolation();
        }

        if (!$deviceType->getIsEndpointDevicesAvailable() && $protocol->getApplyEndpointDevices()) {
            $this->context->buildViolation($constraint->messageEndpointDevicesDisabled)->atPath('applyEndpointDevices')->addViolation();
        }

        if (!$deviceType->getHasVariables() && $protocol->getApplyVariables()) {
            $this->context->buildViolation($constraint->messageVariablesDisabled)->atPath('applyVariables')->addViolation();
        }

        $this->validateConfigs($deviceType, $protocol, $constraint);
        $this->validateFirmwares($deviceType, $protocol, $constraint);
    }

    protected function validateConfigs(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        // Decided not to check hasAlwaysReinstallConfig flag for easier UX during API call
        // Cleanup code added to controller
        if (!$deviceType->getHasConfig1() && $protocol->getReinstallConfig1()) {
            $this->context->buildViolation($constraint->messageConfig1Disabled)->atPath('reinstallConfig1')->addViolation();
        }

        if (!$deviceType->getHasConfig2() && $protocol->getReinstallConfig2()) {
            $this->context->buildViolation($constraint->messageConfig2Disabled)->atPath('reinstallConfig2')->addViolation();
        }

        if (!$deviceType->getHasConfig3() && $protocol->getReinstallConfig3()) {
            $this->context->buildViolation($constraint->messageConfig3Disabled)->atPath('reinstallConfig3')->addViolation();
        }
    }

    protected function validateFirmwares(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getHasFirmware1() && $protocol->getReinstallFirmware1()) {
            $this->context->buildViolation($constraint->messageFirmware1Disabled)->atPath('reinstallFirmware1')->addViolation();
        }

        if (!$deviceType->getHasFirmware2() && $protocol->getReinstallFirmware2()) {
            $this->context->buildViolation($constraint->messageFirmware2Disabled)->atPath('reinstallFirmware2')->addViolation();
        }

        if (!$deviceType->getHasFirmware3() && $protocol->getReinstallFirmware3()) {
            $this->context->buildViolation($constraint->messageFirmware3Disabled)->atPath('reinstallFirmware3')->addViolation();
        }
    }
}
