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

trait VpnClientEntityTrait
{
    use OpenVpnClientEntityTrait;

    #[Groups(['device:openVpnPublic', 'user:openVpnPublic', 'vpnConnection:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $vpnIp = null;

    #[Groups(['device:openVpnPublic', 'user:openVpnPublic', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $vpnIpSortable = null;

    #[Groups(['device:openVpnPublic', 'user:openVpnPublic', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $vpnConnected = false;

    public function getVpnIp(): ?string
    {
        return $this->vpnIp;
    }

    public function setVpnIp(?string $vpnIp)
    {
        $this->vpnIp = $vpnIp;
    }

    public function getVpnIpSortable(): ?int
    {
        return $this->vpnIpSortable;
    }

    public function setVpnIpSortable(?int $vpnIpSortable)
    {
        $this->vpnIpSortable = $vpnIpSortable;
    }

    public function getVpnConnected(): ?bool
    {
        return $this->vpnConnected;
    }

    public function setVpnConnected(?bool $vpnConnected)
    {
        $this->vpnConnected = $vpnConnected;
    }
}
