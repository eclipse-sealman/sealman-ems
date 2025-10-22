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

namespace App\Entity;

use App\Model\AuditableInterface;
use App\Validator\Constraints\Variable as VariableValidator;
use App\Validator\Constraints\VariableName;
use App\Validator\Constraints\VariablePredefined as VariablePredefinedValidator;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[VariableValidator(groups: ['device:common'])]
#[VariablePredefinedValidator(groups: ['device:common'])]
class DeviceVariable implements AuditableInterface
{
    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Name.
     */
    #[Groups(['variables:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['device:common'])]
    #[VariableName(groups: ['device:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Variable value.
     */
    #[Groups(['variables:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['device:common'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $variableValue = null;

    /**
     * Device.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'variables')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Device $device = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getVariableValue(): ?string
    {
        return $this->variableValue;
    }

    public function setVariableValue(?string $variableValue)
    {
        $this->variableValue = $variableValue;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }
}
