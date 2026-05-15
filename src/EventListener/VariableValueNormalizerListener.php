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

namespace App\EventListener;

use App\Entity\DeviceVariable;
use App\Entity\ImportFileRowVariable;
use App\Entity\TemplateVersionVariable;
use App\Service\Helper\VariableManagerTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::prePersist, method: 'normalizeValue', entity: DeviceVariable::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'normalizeValue', entity: DeviceVariable::class)]
#[AsEntityListener(event: Events::prePersist, method: 'normalizeValue', entity: ImportFileRowVariable::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'normalizeValue', entity: ImportFileRowVariable::class)]
#[AsEntityListener(event: Events::prePersist, method: 'normalizeValue', entity: TemplateVersionVariable::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'normalizeValue', entity: TemplateVersionVariable::class)]
class VariableValueNormalizerListener
{
    use VariableManagerTrait;

    public function normalizeValue(DeviceVariable|ImportFileRowVariable|TemplateVersionVariable $variable): void
    {
        if (!$variable->getVariableType()) {
            return;
        }

        if (null === $variable->getVariableValue()) {
            return;
        }

        $normalizedValue = $this->variableManager->getNormalizedVariableValue(
            $variable->getVariableType(),
            $variable->getVariableValue()
        );
        $variable->setVariableValue($normalizedValue);
    }
}
