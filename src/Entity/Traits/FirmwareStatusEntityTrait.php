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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait FirmwareStatusEntityTrait
{
    #[Groups(['firmwareStatus', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $firmwareVersion1 = null;

    #[Groups(['firmwareStatus', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $firmwareVersion2 = null;

    #[Groups(['firmwareStatus', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $firmwareVersion3 = null;

    public function getFirmwareVersion1(): ?string
    {
        return $this->firmwareVersion1;
    }

    public function setFirmwareVersion1(?string $firmwareVersion1)
    {
        $this->firmwareVersion1 = $firmwareVersion1;
    }

    public function getFirmwareVersion2(): ?string
    {
        return $this->firmwareVersion2;
    }

    public function setFirmwareVersion2(?string $firmwareVersion2)
    {
        $this->firmwareVersion2 = $firmwareVersion2;
    }

    public function getFirmwareVersion3(): ?string
    {
        return $this->firmwareVersion3;
    }

    public function setFirmwareVersion3(?string $firmwareVersion3)
    {
        $this->firmwareVersion3 = $firmwareVersion3;
    }
}
