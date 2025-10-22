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

use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\DeviceVariable;
use App\Entity\ImportFileRowVariable;
use App\Entity\TemplateVersionVariable;
use App\Model\VariableInterface;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class VariablePredefinedValidator extends ConstraintValidator
{
    use DeviceCommunicationFactoryTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        $predefinedVariableNames = [];
        if ($protocol instanceof TemplateVersionVariable) {
            $templateVersion = $protocol->getTemplateVersion();
            if (!$templateVersion->getDeviceType()) {
                throw new \Exception('VariablePredefinedValidator requires '.TemplateVersionVariable::class.' to have deviceType set');
            }
            $predefinedVariableNames = $this->getDeviceTypePredefinedVariableNames($templateVersion->getDeviceType());
        } elseif ($protocol instanceof DeviceVariable) {
            $device = $protocol->getDevice();
            $predefinedVariableNames = $this->getDevicePredefinedVariableNames($device);
        } elseif ($protocol instanceof ImportFileRowVariable) {
            $importFileRow = $protocol->getRow();
            $deviceType = $importFileRow->getDeviceType();
            if (!$deviceType) {
                return;
            }

            $predefinedVariableNames = $this->getDeviceTypePredefinedVariableNames($deviceType);
        } else {
            // Cannot use Symfony\Component\Validator\Exception\UnexpectedValueException due to multiple accepted types
            throw new \Exception('VariablePredefinedValidator only supports '.TemplateVersionVariable::class.', '.DeviceVariable::class.' and '.ImportFileRowVariable::class);
        }

        foreach ($predefinedVariableNames as $predefinedVariableName) {
            if ($protocol->getName() == $predefinedVariableName) {
                $this->context->buildViolation($constraint->messageVariableNameUsed)->atPath('name')->addViolation();
            } else {
                if ($this->isUsedByPrefixVariable($protocol->getName(), $predefinedVariableName)) {
                    $this->context->buildViolation($constraint->messageVariableNameUsed)->atPath('name')->addViolation();
                }
            }
        }
    }

    public function isUsedByPrefixVariable(string $variableName, string $predefinedVariableName): bool
    {
        // complex test because prefixed variables don't have to be in predefined array
        $prefixVariableNames = [
            VariableInterface::VARIABLE_NAME_PIP_PREFIX,
            VariableInterface::VARIABLE_NAME_VIP_PREFIX,
            VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_PREFIX,
            VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_PREFIX,
        ];

        foreach ($prefixVariableNames as $prefixVariableName) {
            if (1 === preg_match('/'.$predefinedVariableName.'[\d]*/', $prefixVariableName)) {
                if (1 === preg_match('/'.$predefinedVariableName.'[\d]+/', $variableName)) {
                    return true;
                }
            }
        }

        return false;
    }

    // Both methods do same thing, but are left for correct usage of deviceCommunication class depending on what object is edited (device, template, import)
    public function getDevicePredefinedVariableNames(Device $device): array
    {
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDevice($device);

        if (!$communicationProcedure) {
            // This should never happen
            return [];
        }

        return $communicationProcedure->getAllPredefinedDeviceVariableNames();
    }

    public function getDeviceTypePredefinedVariableNames(DeviceType $deviceType): array
    {
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);

        if (!$communicationProcedure) {
            // This should never happen
            return [];
        }

        return $communicationProcedure->getAllPredefinedDeviceVariableNames();
    }
}
