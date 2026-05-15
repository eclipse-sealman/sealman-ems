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

use App\Entity\Firmware;
use App\Enum\Feature;
use App\Enum\FirmwareVersionSchema;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\FirmwareVersionSchemaManagerTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FirmwareValidator extends ConstraintValidator
{
    use EntityManagerTrait;
    use FirmwareVersionSchemaManagerTrait;
    use SecurityHelperTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        $deviceType = $protocol->getDeviceType();
        if (!$deviceType) {
            return;
        }

        $feature = $protocol->getFeature();
        if (!$feature) {
            return;
        }

        if ($this->hasNameDuplicate($protocol)) {
            $this->context->buildViolation($constraint->messageNameNotUnique)->atPath('name')->addViolation();
        }

        if (Feature::PRIMARY === $feature && !$deviceType->getHasFirmware1()) {
            $this->context->buildViolation($constraint->messageFeatureInvalid)->atPath('feature')->addViolation();
        }

        if (Feature::SECONDARY === $feature && !$deviceType->getHasFirmware2()) {
            $this->context->buildViolation($constraint->messageFeatureInvalid)->atPath('feature')->addViolation();
        }

        if (Feature::TERTIARY === $feature && !$deviceType->getHasFirmware3()) {
            $this->context->buildViolation($constraint->messageFeatureInvalid)->atPath('feature')->addViolation();
        }

        $getFirmwareSchemaFunctionName = 'getFirmwareSchema'.$feature->value;
        $firmwareSchema = $deviceType->$getFirmwareSchemaFunctionName();

        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            if ($protocol->getRequiredFirmware()) {
                $this->context->buildViolation($constraint->messageRequiredFirmwareMustBeNull)->atPath('requiredFirmware')->addViolation();

                return;
            }
        } else {
            if (!$this->firmwareVersionSchemaManager->isVersionValid($protocol->getVersion(), $firmwareSchema)) {
                $this->context->buildViolation($constraint->messageFirmwareVersionNotMatchingSchema)->setParameter('{{ version }}', $protocol->getVersion())->atPath('version')->addViolation();

                return;
            }

            $requiredFirmware = $protocol->getRequiredFirmware();
            $requiredFirmwareIdOption = $this->context->getRoot()->getConfig()->getOption('requiredFirmwareId');
            if (null !== $requiredFirmware?->getId() && $requiredFirmware?->getId() !== $requiredFirmwareIdOption) {
                if (!$this->isFirmwareAccessible($requiredFirmware)) {
                    $this->context->buildViolation($constraint->messageRequiredFirmwareInvalid)->atPath('requiredFirmware')->addViolation();
                }
            }
        }

        $this->validateFirmwareUpdatePath($protocol, $firmwareSchema, $constraint);
    }

    protected function validateFirmwareUpdatePath(Firmware $firmware, FirmwareVersionSchema $firmwareSchema, Constraint $constraint): void
    {
        $currentFirmwareVersion = $firmware->getVersion();
        $currentFirmware = $firmware;
        while ($currentFirmware->getRequiredFirmware()) {
            $requiredFirmware = $currentFirmware->getRequiredFirmware();
            $requiredFirmwareVersion = $requiredFirmware->getVersion();

            if ($requiredFirmware->getDeviceType() !== $firmware->getDeviceType()) {
                $this->context->buildViolation($constraint->messageRequiredFirmwareInvalid)->setParameter('{{ version }}', $requiredFirmwareVersion)->atPath('requiredFirmware')->addViolation();

                return;
            }

            if ($requiredFirmware->getFeature() !== $firmware->getFeature()) {
                $this->context->buildViolation($constraint->messageRequiredFirmwareInvalid)->setParameter('{{ version }}', $requiredFirmwareVersion)->atPath('requiredFirmware')->addViolation();

                return;
            }

            // Add check if required firmware version matches schema - if not, add violation - so system will not lock in case of invalid database state
            try {
                // Testing if $currentFirmwareVersion is higher than $requiredFirmwareVersion - if not, add violation
                if (!$this->firmwareVersionSchemaManager->isVersion1HigherThanVersion2(
                    $currentFirmwareVersion,
                    $requiredFirmwareVersion,
                    $firmwareSchema)
                ) {
                    $this->context->buildViolation($constraint->messageRequiredFirmwareNotHigherVersion)->setParameter('{{ version }}', $requiredFirmwareVersion)->atPath('requiredFirmware')->addViolation();

                    return;
                }
            } catch (\InvalidArgumentException $e) {
                $this->context->buildViolation($constraint->messageRequiredFirmwareInvalid)->setParameter('{{ version }}', $requiredFirmwareVersion)->atPath('requiredFirmware')->addViolation();

                return;
            }

            $currentFirmwareVersion = $requiredFirmwareVersion;
            $currentFirmware = $requiredFirmware;
        }
    }

    protected function hasNameDuplicate(Firmware $firmware): bool
    {
        $queryBuilder = $this->getRepository(Firmware::class)->createQueryBuilder('f');
        $queryBuilder->andWhere('f.deviceType = :deviceType');
        $queryBuilder->andWhere('f.name = :name');
        $queryBuilder->setParameter('name', $firmware->getName());
        $queryBuilder->setParameter('deviceType', $firmware->getDeviceType());

        if ($firmware->getId()) {
            $queryBuilder->andWhere('f.id != :id');
            $queryBuilder->setParameter('id', $firmware->getId());
        }

        $queryBuilder->setMaxResults(1);

        $duplicateNameFirmware = $queryBuilder->getQuery()->getOneOrNullResult();

        return $duplicateNameFirmware ? true : false;
    }
}
