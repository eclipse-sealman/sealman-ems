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

use App\Enum\VpnSubnetType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class VpnSubnet
{
    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * VPN Subnet type (Device VPN IP, Technician VPN IP, Devices VPN Virtual IP).
     */
    #[ORM\Column(type: Types::STRING, enumType: VpnSubnetType::class)]
    private ?VpnSubnetType $type = null;

    #[Groups(['vpnSubnet:public'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $ip = null;

    #[Groups(['vpnSubnet:public'])]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $ipLong = null;

    /**
     * Cidr in short format as in /8, /24.
     */
    #[Groups(['vpnSubnet:public'])]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $cidr = null;

    /**
     * Amount of addreses in this subnet.
     */
    #[Groups(['vpnSubnet:public'])]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $size = null;

    /**
     * Network provided in configuration - parent designation for validators.
     */
    #[Groups(['vpnSubnet:public'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $network = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) 'VpnSubnet';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip)
    {
        $this->ip = $ip;
    }

    public function getIpLong(): ?int
    {
        return $this->ipLong;
    }

    public function setIpLong(?int $ipLong)
    {
        $this->ipLong = $ipLong;
    }

    public function getCidr(): ?int
    {
        return $this->cidr;
    }

    public function setCidr(?int $cidr)
    {
        $this->cidr = $cidr;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size)
    {
        $this->size = $size;
    }

    public function getNetwork(): ?string
    {
        return $this->network;
    }

    public function setNetwork(?string $network)
    {
        $this->network = $network;
    }

    public function getType(): ?VpnSubnetType
    {
        return $this->type;
    }

    public function setType(?VpnSubnetType $type)
    {
        $this->type = $type;
    }
}
