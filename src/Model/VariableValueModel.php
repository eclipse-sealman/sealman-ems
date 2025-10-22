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

namespace App\Model;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Model containing variableName and value.
 */
class VariableValueModel
{
    /**
     * Variable name.
     */
    #[Groups(['variable:valueModel'])]
    private ?string $name = null;

    /**
     * Variable value.
     */
    #[Groups(['variable:valueModel'])]
    private ?string $variableValue = null;

    public function __construct(?string $name = null, ?string $variableValue = null)
    {
        $this->name = $name;
        $this->variableValue = $variableValue;
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
}
