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

use App\Entity\Config;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use App\Service\Helper\ConfigManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConfigValidator extends ConstraintValidator
{
    use EntityManagerTrait;
    use ConfigurationManagerTrait;
    use ConfigManagerTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        if (ConfigGenerator::PHP === $protocol->getGenerator() && !$this->getConfiguration()->getConfigGeneratorPhp()) {
            $this->context->buildViolation($constraint->messageConfigGeneratorPhpDisabled)->atPath('generator')->addViolation();

            return;
        }

        if (ConfigGenerator::TWIG === $protocol->getGenerator() && !$this->getConfiguration()->getConfigGeneratorTwig()) {
            $this->context->buildViolation($constraint->messageConfigGeneratorTwigDisabled)->atPath('generator')->addViolation();

            return;
        }

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

        if (Feature::PRIMARY === $feature && !$deviceType->getHasConfig1()) {
            $this->context->buildViolation($constraint->messageFeatureInvalid)->atPath('feature')->addViolation();
        }

        if (Feature::SECONDARY === $feature && !$deviceType->getHasConfig2()) {
            $this->context->buildViolation($constraint->messageFeatureInvalid)->atPath('feature')->addViolation();
        }

        if (Feature::TERTIARY === $feature && !$deviceType->getHasConfig3()) {
            $this->context->buildViolation($constraint->messageFeatureInvalid)->atPath('feature')->addViolation();
        }

        if (ConfigGenerator::TWIG === $protocol->getGenerator()) {
            $content = $protocol->getContent();
            $result = $this->configManager->validateConfigTwig($content);
            if (null !== $result) {
                $this->context->buildViolation($constraint->messageTwigInvalid, ['message' => $result])->atPath('content')->addViolation();
            }
        }

        // No possibility to validate PHP config
    }

    protected function hasNameDuplicate(Config $config): bool
    {
        $queryBuilder = $this->getRepository(Config::class)->createQueryBuilder('f');
        $queryBuilder->andWhere('f.deviceType = :deviceType');
        $queryBuilder->andWhere('f.name = :name');
        $queryBuilder->setParameter('name', $config->getName());
        $queryBuilder->setParameter('deviceType', $config->getDeviceType());

        if ($config->getId()) {
            $queryBuilder->andWhere('f.id != :id');
            $queryBuilder->setParameter('id', $config->getId());
        }

        $queryBuilder->setMaxResults(1);

        $duplicateNameConfig = $queryBuilder->getQuery()->getOneOrNullResult();

        return $duplicateNameConfig ? true : false;
    }
}
