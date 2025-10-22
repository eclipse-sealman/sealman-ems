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

trait GsmEntityTrait
{
    #[Groups(['gsm:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $imei = null;

    #[Groups(['gsm:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $imsi = null;

    #[Groups(['gsm:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $imsi2 = null;

    #[Groups(['gsm:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $operatorCode = null;

    #[Groups(['gsm:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $band = null;

    #[Groups(['gsm:admin', 'gsm:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cellId = null;

    #[Groups(['gsm:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $networkGeneration = null;

    #[Groups(['gsm:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $rsrp = null;

    // TODO processed rsrp value - maybe we should somehow adjust it to one standard e.g. ASU - Ask customer
    #[Groups(['gsm:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $rsrpValue = null;

    #[Groups(['gsm:admin', 'gsm:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cellularIp1 = null;

    #[Groups(['gsm:admin', 'gsm:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cellularUptime1 = null;

    #[Groups(['gsm:admin', 'gsm:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $cellularUptimeSeconds1 = null;

    #[Groups(['gsm:admin', 'gsm:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cellularIp2 = null;

    #[Groups(['gsm:admin', 'gsm:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cellularUptime2 = null;

    #[Groups(['gsm:admin', 'gsm:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $cellularUptimeSeconds2 = null;

    public function getImei(): ?string
    {
        return $this->imei;
    }

    public function setImei(?string $imei)
    {
        $this->imei = $imei;
    }

    public function getImsi(): ?string
    {
        return $this->imsi;
    }

    public function setImsi(?string $imsi)
    {
        $this->imsi = $imsi;
    }

    public function getImsi2(): ?string
    {
        return $this->imsi2;
    }

    public function setImsi2(?string $imsi2)
    {
        $this->imsi2 = $imsi2;
    }

    public function getOperatorCode(): ?string
    {
        return $this->operatorCode;
    }

    public function setOperatorCode(?string $operatorCode)
    {
        $this->operatorCode = $operatorCode;
    }

    public function getBand(): ?string
    {
        return $this->band;
    }

    public function setBand(?string $band)
    {
        $this->band = $band;
    }

    public function getCellId(): ?string
    {
        return $this->cellId;
    }

    public function setCellId(?string $cellId)
    {
        $this->cellId = $cellId;
    }

    public function getRsrp(): ?string
    {
        return $this->rsrp;
    }

    public function setRsrp(?string $rsrp)
    {
        $this->rsrp = $rsrp;
    }

    public function getRsrpValue(): ?int
    {
        return $this->rsrpValue;
    }

    public function setRsrpValue(?int $rsrpValue)
    {
        $this->rsrpValue = $rsrpValue;
    }

    public function getCellularIp1(): ?string
    {
        return $this->cellularIp1;
    }

    public function setCellularIp1(?string $cellularIp1)
    {
        $this->cellularIp1 = $cellularIp1;
    }

    public function getCellularUptime1(): ?string
    {
        return $this->cellularUptime1;
    }

    public function setCellularUptime1(?string $cellularUptime1)
    {
        $this->cellularUptime1 = $cellularUptime1;
    }

    public function getCellularUptimeSeconds1(): ?int
    {
        return $this->cellularUptimeSeconds1;
    }

    public function setCellularUptimeSeconds1(?int $cellularUptimeSeconds1)
    {
        $this->cellularUptimeSeconds1 = $cellularUptimeSeconds1;
    }

    public function getCellularIp2(): ?string
    {
        return $this->cellularIp2;
    }

    public function setCellularIp2(?string $cellularIp2)
    {
        $this->cellularIp2 = $cellularIp2;
    }

    public function getCellularUptime2(): ?string
    {
        return $this->cellularUptime2;
    }

    public function setCellularUptime2(?string $cellularUptime2)
    {
        $this->cellularUptime2 = $cellularUptime2;
    }

    public function getCellularUptimeSeconds2(): ?int
    {
        return $this->cellularUptimeSeconds2;
    }

    public function setCellularUptimeSeconds2(?int $cellularUptimeSeconds2)
    {
        $this->cellularUptimeSeconds2 = $cellularUptimeSeconds2;
    }

    public function getNetworkGeneration(): ?string
    {
        return $this->networkGeneration;
    }

    public function setNetworkGeneration(?string $networkGeneration)
    {
        $this->networkGeneration = $networkGeneration;
    }
}
