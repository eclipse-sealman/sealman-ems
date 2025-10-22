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
use App\Entity\DeviceTypeSecret;
use App\Model\VariableInterface;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SecretVariablePrefixValidator extends ConstraintValidator
{
    use DeviceCommunicationFactoryTrait;
    use EntityManagerTrait;

    public function validate($deviceTypeSecret, Constraint $constraint): void
    {
        if (!$deviceTypeSecret) {
            return;
        }

        if (!$deviceTypeSecret->getDeviceType()) {
            return;
        }

        // No validation needed
        if (!$deviceTypeSecret->getUseAsVariable()) {
            return;
        }

        if (!$deviceTypeSecret->getVariableNamePrefix()) {
            // Cannot perform validation without variable name prefix which is validated on entity level
            return;
        }

        if ($this->hasVariableNamePrefixDuplicate($deviceTypeSecret)) {
            $this->context->buildViolation($constraint->messageVariablePrefixUsedInDeviceTypeSecret)->atPath('variableNamePrefix')->addViolation();

            return;
        }

        $predefinedVariableNames = $this->getDeviceTypePredefinedVariableNames($deviceTypeSecret->getDeviceType());

        foreach ($predefinedVariableNames as $predefinedVariableName) {
            if ($deviceTypeSecret->getVariableNamePrefix() == $predefinedVariableName) {
                $this->context->buildViolation($constraint->messageVariablePrefixUsedInPredefinedVariabled)->atPath('variableNamePrefix')->addViolation();
            } else {
                if ($this->isUsedByPrefixVariable($deviceTypeSecret->getVariableNamePrefix(), $predefinedVariableName)) {
                    $this->context->buildViolation($constraint->messageVariablePrefixUsedInPredefinedVariabled)->atPath('variableNamePrefix')->addViolation();
                }
            }
        }
    }

    protected function hasVariableNamePrefixDuplicate(DeviceTypeSecret $deviceTypeSecret): bool
    {
        $queryBuilder = $this->getRepository(DeviceTypeSecret::class)->createQueryBuilder('dts');
        $queryBuilder->andWhere('dts.deviceType = :deviceType');
        $queryBuilder->andWhere('dts.useAsVariable = :useAsVariable');
        $queryBuilder->andWhere('dts.variableNamePrefix = :variableNamePrefix');
        $queryBuilder->setParameter('useAsVariable', true);
        $queryBuilder->setParameter('variableNamePrefix', $deviceTypeSecret->getVariableNamePrefix());
        $queryBuilder->setParameter('deviceType', $deviceTypeSecret->getDeviceType());

        if ($deviceTypeSecret->getId()) {
            $queryBuilder->andWhere('dts.id != :id');
            $queryBuilder->setParameter('id', $deviceTypeSecret->getId());
        }

        $queryBuilder->setMaxResults(1);

        $duplicateVariableNamePrefix = $queryBuilder->getQuery()->getOneOrNullResult();

        return $duplicateVariableNamePrefix ? true : false;
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
