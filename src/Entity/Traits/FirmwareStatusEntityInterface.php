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

interface FirmwareStatusEntityInterface
{
    public function getFirmwareVersion1(): ?string;

    public function setFirmwareVersion1(?string $firmwareVersion1);

    public function getFirmwareVersion2(): ?string;

    public function setFirmwareVersion2(?string $firmwareVersion2);

    public function getFirmwareVersion3(): ?string;

    public function setFirmwareVersion3(?string $firmwareVersion3);
}
