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

class FirmwareHardwareFileValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        $firmware = $protocol->getFirmware();
        if (!$firmware) {
            return;
        }

        $firmwareDeviceType = $firmware?->getDeviceType();
        $hardwareDeviceType = $protocol->getHardware()?->getDeviceType();
        if (!$firmwareDeviceType || !$hardwareDeviceType) {
            return;
        }

        if ($firmwareDeviceType->getId() !== $hardwareDeviceType->getId()) {
            $this->context->buildViolation($constraint->messageInvalidDeviceType)->atPath('hardware')->addViolation();
            $this->context->buildViolation($constraint->messageInvalidDeviceType)->atPath('firmware')->addViolation();
        }

        if (!$firmware->getEnableHardwareFiles()) {
            $this->context->buildViolation($constraint->messageFirmwareHardwareFilesDisabled)->atPath('firmware')->addViolation();
        }
    }
}
