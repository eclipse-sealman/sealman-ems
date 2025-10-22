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

use App\Entity\Traits\BlameableEntityTrait;
use App\Entity\Traits\BlameableEntityInterface;
use App\Entity\Traits\CreatedAtEntityTrait;
use App\Entity\Traits\CreatedAtEntityInterface;
use App\Model\AuditableInterface;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class VpnConnection implements DenyInterface, CreatedAtEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use CreatedAtEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // Is permanent connection (eg. monitoring device connection to whole network)?
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $permanent = false;

    // Source connection ip network address - used in permanent connections
    #[Groups(['vpnConnection:deviceToNetworkPublic', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $source = null;

    // Destination connection network addresses - used in permanent connections
    #[Groups(['vpnConnection:deviceToNetworkPublic', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $destination = null;

    #[Groups(['vpnConnection:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: DeviceEndpointDevice::class, inversedBy: 'vpnConnections')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceEndpointDevice $endpointDevice = null;

    #[Groups(['vpnConnection:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'vpnConnections')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Device $device = null;

    #[Groups(['vpnConnection:public', 'vpnConnection:device', 'vpnConnection:deviceEndpointDevice', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'vpnConnections')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[Groups(['vpnConnection:public', 'vpnConnection:device', 'vpnConnection:deviceEndpointDevice', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $connectionStartAt;

    #[Groups(['vpnConnection:public', 'vpnConnection:device', 'vpnConnection:deviceEndpointDevice', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $connectionEndAt = null;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $connectionFirewallRules = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) 'Connection';
    }

    public function setTarget(Device|DeviceEndpointDevice $target)
    {
        if ($target instanceof Device) {
            $this->setDevice($target);
        } elseif ($target instanceof DeviceEndpointDevice) {
            $this->setEndpointDevice($target);
        }
    }

    #[Groups(['vpnConnection:public', 'vpnConnection:device', 'vpnConnection:deviceEndpointDevice'])]
    public function getTarget(): Device|DeviceEndpointDevice|null
    {
        if ($this->getDevice()) {
            return $this->getDevice();
        }
        if ($this->getEndpointDevice()) {
            return $this->getEndpointDevice();
        }

        return null;
    }

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getPermanent(): ?bool
    {
        return $this->permanent;
    }

    public function setPermanent(?bool $permanent)
    {
        $this->permanent = $permanent;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source)
    {
        $this->source = $source;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination)
    {
        $this->destination = $destination;
    }

    public function getEndpointDevice(): ?DeviceEndpointDevice
    {
        return $this->endpointDevice;
    }

    public function setEndpointDevice(?DeviceEndpointDevice $endpointDevice)
    {
        $this->endpointDevice = $endpointDevice;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function getConnectionStartAt(): ?\DateTime
    {
        return $this->connectionStartAt;
    }

    public function setConnectionStartAt(?\DateTime $connectionStartAt)
    {
        $this->connectionStartAt = $connectionStartAt;
    }

    public function getConnectionEndAt(): ?\DateTime
    {
        return $this->connectionEndAt;
    }

    public function setConnectionEndAt(?\DateTime $connectionEndAt)
    {
        $this->connectionEndAt = $connectionEndAt;
    }

    public function getConnectionFirewallRules(): ?array
    {
        return $this->connectionFirewallRules;
    }

    public function setConnectionFirewallRules(?array $connectionFirewallRules)
    {
        $this->connectionFirewallRules = $connectionFirewallRules;
    }
}
