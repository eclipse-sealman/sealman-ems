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

trait VpnClientDeviceEntityTrait
{
    use VpnDeviceEntityTrait;
    use VpnClientEntityTrait;

    #[Groups(['device:vpnDevicePublic', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $vpnTrafficIn = null;

    #[Groups(['device:vpnDevicePublic', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $vpnTrafficOut = null;

    #[Groups(['device:vpnClientPublic', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $virtualSubnetIp = null;

    #[Groups(['device:vpnClientPublic', 'deviceEndpointDevice:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $virtualSubnetIpSortable = null;

    #[Groups(['device:vpnClientPublic', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $virtualSubnet = null;

    public function getVirtualSubnetIp(): ?string
    {
        return $this->virtualSubnetIp;
    }

    public function setVirtualSubnetIp(?string $virtualSubnetIp)
    {
        $this->virtualSubnetIp = $virtualSubnetIp;
    }

    public function getVirtualSubnetIpSortable(): ?int
    {
        return $this->virtualSubnetIpSortable;
    }

    public function setVirtualSubnetIpSortable(?int $virtualSubnetIpSortable)
    {
        $this->virtualSubnetIpSortable = $virtualSubnetIpSortable;
    }

    public function getVirtualSubnet(): ?string
    {
        return $this->virtualSubnet;
    }

    public function setVirtualSubnet(?string $virtualSubnet)
    {
        $this->virtualSubnet = $virtualSubnet;
    }

    public function getVpnTrafficIn(): ?int
    {
        return $this->vpnTrafficIn;
    }

    public function setVpnTrafficIn(?int $vpnTrafficIn)
    {
        $this->vpnTrafficIn = $vpnTrafficIn;
    }

    public function getVpnTrafficOut(): ?int
    {
        return $this->vpnTrafficOut;
    }

    public function setVpnTrafficOut(?int $vpnTrafficOut)
    {
        $this->vpnTrafficOut = $vpnTrafficOut;
    }
}
