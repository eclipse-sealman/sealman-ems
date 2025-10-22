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

trait VpnDeviceEntityTrait
{
    #[Groups(['device:vpnDevicePublic', 'deviceEndpointDevice:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $vpnLastConnectionAt = null;

    #[Groups(['device:vpnDevicePublic', 'deviceEndpointDevice:public', 'vpnConnection:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $virtualIp = null;

    #[Groups(['device:vpnDevicePublic', 'deviceEndpointDevice:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $virtualIpSortable = null;

    public function getVpnLastConnectionAt(): ?\DateTime
    {
        return $this->vpnLastConnectionAt;
    }

    public function setVpnLastConnectionAt(?\DateTime $vpnLastConnectionAt)
    {
        $this->vpnLastConnectionAt = $vpnLastConnectionAt;
    }

    public function getVirtualIp(): ?string
    {
        return $this->virtualIp;
    }

    public function setVirtualIp(?string $virtualIp)
    {
        $this->virtualIp = $virtualIp;
    }

    public function getVirtualIpSortable(): ?int
    {
        return $this->virtualIpSortable;
    }

    public function setVirtualIpSortable(?int $virtualIpSortable)
    {
        $this->virtualIpSortable = $virtualIpSortable;
    }
}
