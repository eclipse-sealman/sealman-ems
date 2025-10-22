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

use Carve\ApiBundle\Validator\Constraints as Assert;

class SgGatewayModel
{
    /**
     * Hardware version - model.
     */
    #[Assert\NotBlank(groups: ['fieldModelRequired'])]
    #[Assert\Length(max: 255)]
    private ?string $hardwareVersion = null;

    /**
     * Firmware version.
     */
    #[Assert\NotBlank(groups: ['sgGatewayConfiguration'])]
    #[Assert\Length(max: 255)]
    private ?string $firmwareVersion = null;

    /**
     * Serial number.
     */
    #[Assert\NotBlank(groups: ['fieldSerialNumberRequired'])]
    #[Assert\Length(max: 255, groups: ['Default', 'authentication'])]
    private ?string $serialNumber = null;

    public function getHardwareVersion(): ?string
    {
        return $this->hardwareVersion;
    }

    public function setHardwareVersion(?string $hardwareVersion)
    {
        $this->hardwareVersion = $hardwareVersion;
    }

    public function getFirmwareVersion(): ?string
    {
        return $this->firmwareVersion;
    }

    public function setFirmwareVersion(?string $firmwareVersion)
    {
        $this->firmwareVersion = $firmwareVersion;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber)
    {
        $this->serialNumber = $serialNumber;
    }
}
