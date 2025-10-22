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
use App\Enum\SourceType;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FirmwareValidator extends ConstraintValidator
{
    use EntityManagerTrait;

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

        $sourceType = $protocol->getSourceType();
        if (!$sourceType) {
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

        if (SourceType::UPLOAD === $sourceType) {
            if (!$protocol->getFilepath()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('filepath')->addViolation();
            }
        }

        if (SourceType::EXTERNAL_URL === $sourceType) {
            if (!$protocol->getMd5()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('md5')->addViolation();
            }

            if (!$protocol->getExternalUrl()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('externalUrl')->addViolation();
            }
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
