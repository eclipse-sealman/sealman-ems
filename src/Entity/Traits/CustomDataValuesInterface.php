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

/**
 * Interface for entities that store custom data values extracted from communication payloads.
 *
 * Extends CustomDataInterface and adds getValue/setValue methods for the stored value.
 * Used by DeviceCustomData and CommunicationLogCustomData entities.
 */
interface CustomDataValuesInterface extends CustomDataInterface
{
    public function getValue(): ?string;

    public function setValue(?string $value);
}
