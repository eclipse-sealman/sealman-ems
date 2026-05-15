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

namespace App\Entity\Traits;

use App\Model\AuditableInterface;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Trait for entities that store custom data values extracted from communication payloads.
 *
 * Provides the value field for storing custom data. Should be used with CustomDataTrait
 * to provide the complete set of custom data fields (name, type, variableName, value).
 *
 * Used by DeviceCustomData and CommunicationLogCustomData entities.
 */
trait CustomDataValuesTrait
{
    use CustomDataTrait;

    /**
     * Custom data value - stored as string, will be cast based on type field.
     */
    #[Assert\NotBlank(groups: ['customData:common'])]
    #[Groups(['customData:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $value = null;

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value)
    {
        $this->value = $value;
    }
}
