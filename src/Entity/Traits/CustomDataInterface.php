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

/**
 * Interface for entities that contain custom data definition fields (name, type, variableName).
 *
 * Used by entities that define custom data mappings or store custom data values.
 */
interface CustomDataInterface
{
    public function getName(): ?string;

    public function setName(?string $name);

    public function getType(): ?VariableType;

    public function setType(?VariableType $type);

    public function getVariableName(): ?string;

    public function setVariableName(?string $variableName);
}
