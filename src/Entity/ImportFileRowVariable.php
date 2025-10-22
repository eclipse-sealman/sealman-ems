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

use App\Validator\Constraints\Variable as VariableValidator;
use App\Validator\Constraints\VariableName;
use App\Validator\Constraints\VariablePredefined as VariablePredefinedValidator;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[VariableValidator(groups: ['importFileRow:import'])]
#[VariablePredefinedValidator(groups: ['importFileRow:import'])]
class ImportFileRowVariable
{
    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Name.
     */
    #[Groups(['importFileRow:public'])]
    #[VariableName(groups: ['importFileRow:import'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Variable value.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $variableValue = null;

    /**
     * Import file row.
     */
    #[ORM\ManyToOne(targetEntity: ImportFileRow::class, inversedBy: 'variables')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ImportFileRow $row = null;

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

    public function getRow(): ?ImportFileRow
    {
        return $this->row;
    }

    public function setRow(?ImportFileRow $row)
    {
        $this->row = $row;
    }
}
