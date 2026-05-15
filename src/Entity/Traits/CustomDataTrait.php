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

use App\Enum\VariableType;
use App\Model\AuditableInterface;
use App\Validator\Constraints\VariableValidPhpName;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Trait for entities that define or store custom data fields.
 *
 * Provides common fields: name, variableEnabled, type, and variableName.
 * Used by DeviceTypeCustomDataMapping (definitions), DeviceCustomData, and CommunicationLogCustomData (values).
 */
trait CustomDataTrait
{
    /**
     * Custom data name - user-defined identifier for easy reference.
     */
    #[Assert\NotBlank(groups: ['customData:common'])]
    #[Groups(['customData:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Should variable be created from this custom data.
     */
    #[Groups(['customData:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $variableEnabled = false;

    /**
     * Data type - defines how the value should be interpreted and cast.
     */
    #[Assert\NotBlankOnTrue(propertyPath: 'variableEnabled', groups: ['customData:common'])]
    #[Groups(['customData:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: VariableType::class, nullable: true)]
    private ?VariableType $type = null;

    /**
     * Variable name for use in config generation (optional).
     */
    #[Assert\NotBlankOnTrue(propertyPath: 'variableEnabled', groups: ['customData:common'])]
    #[Groups(['customData:public', AuditableInterface::GROUP])]
    #[VariableValidPhpName(groups: ['customData:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $variableName = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getType(): ?VariableType
    {
        return $this->type;
    }

    public function setType(?VariableType $type)
    {
        $this->type = $type;
    }

    public function getVariableName(): ?string
    {
        return $this->variableName;
    }

    public function setVariableName(?string $variableName)
    {
        $this->variableName = $variableName;
    }

    public function getVariableEnabled(): ?bool
    {
        return $this->variableEnabled;
    }

    public function setVariableEnabled(?bool $variableEnabled)
    {
        $this->variableEnabled = $variableEnabled;
    }
}
