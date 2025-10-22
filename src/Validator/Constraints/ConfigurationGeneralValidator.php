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
use App\Enum\CommunicationProcedure;
use App\Enum\FieldRequirement;
use App\Enum\RouterIdentifier;
use App\Service\Helper\ConfigurationManagerTrait;
use Carve\ApiBundle\Service\Helper\EntityManagerTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConfigurationGeneralValidator extends ConstraintValidator
{
    use ConfigurationManagerTrait;
    use EntityManagerTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        if (!$protocol->getConfigGeneratorPhp() && !$protocol->getConfigGeneratorTwig()) {
            $this->context->buildViolation($constraint->messageGeneratorAtLeastOne)->atPath('configGeneratorPhp')->addViolation();
            $this->context->buildViolation($constraint->messageGeneratorAtLeastOne)->atPath('configGeneratorTwig')->addViolation();
        } else {
            if (!$protocol->getConfigGeneratorPhp() && $this->configurationManager->isConfigGeneratorPhpUsed()) {
                $this->context->buildViolation($constraint->messageGeneratorUsed)->atPath('configGeneratorPhp')->addViolation();
            }

            if (!$protocol->getConfigGeneratorTwig() && $this->configurationManager->isConfigGeneratorTwigUsed()) {
                $this->context->buildViolation($constraint->messageGeneratorUsed)->atPath('configGeneratorTwig')->addViolation();
            }
        }

        $previousConfiguration = $this->entityManager->getUnitOfWork()->getOriginalEntityData($protocol);

        if ($protocol->getRouterIdentifier() != $previousConfiguration['routerIdentifier']) {
            $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');
            $queryBuilder->leftJoin('d.deviceType', 'dt');
            $queryBuilder->andWhere('dt.communicationProcedure IN (:communicationProcedure)');
            $queryBuilder->setParameter('communicationProcedure', [
                CommunicationProcedure::ROUTER_ONE_CONFIG,
                CommunicationProcedure::ROUTER,
                CommunicationProcedure::ROUTER_DSA,
            ]);
            $queryBuilder->setMaxResults(1);

            $deviceExists = $queryBuilder->getQuery()->getOneOrNullResult();

            if ($deviceExists) {
                $this->context->buildViolation($constraint->messageDeviceExists)->atPath('routerIdentifier')->addViolation();
            } else {
                if (RouterIdentifier::IMSI == $protocol->getRouterIdentifier()) {
                    $queryBuilder = $this->getRepository(DeviceType::class)->createQueryBuilder('dt');
                    $queryBuilder->andWhere('dt.communicationProcedure IN (:communicationProcedure)');
                    $queryBuilder->setParameter('communicationProcedure', [
                        CommunicationProcedure::ROUTER_ONE_CONFIG,
                        CommunicationProcedure::ROUTER,
                        CommunicationProcedure::ROUTER_DSA,
                    ]);
                    $queryBuilder->andWhere('dt.fieldImsi != :fieldImsiRequiredInCommunication ');
                    $queryBuilder->andWhere(' dt.fieldImsi != :fieldImsiRequired');
                    $queryBuilder->setParameter('fieldImsiRequiredInCommunication', FieldRequirement::REQUIRED_IN_COMMUNICATION);
                    $queryBuilder->setParameter('fieldImsiRequired', FieldRequirement::REQUIRED);
                    $queryBuilder->setMaxResults(1);

                    $deviceTypeNotValid = $queryBuilder->getQuery()->getOneOrNullResult();

                    if ($deviceTypeNotValid) {
                        $this->context->buildViolation($constraint->messageDeviceTypeRequiredWithHasImsi)->atPath('routerIdentifier')->addViolation();
                    }
                }
            }
        }
    }
}
