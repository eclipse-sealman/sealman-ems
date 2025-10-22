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

use App\Entity\Traits\BlameableEntityInterface;
use App\Entity\Traits\BlameableEntityTrait;
use App\Entity\Traits\CreatedAtEntityInterface;
use App\Entity\Traits\CreatedAtEntityTrait;
use App\Entity\Traits\LogLevelEntityTrait;
use App\Entity\Traits\LogLevelInterface;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Index(name: 'idx_createdAt', columns: ['created_at'])]
#[ORM\Index(name: 'idx_createdAt_id', columns: ['created_at', 'id'])]
class VpnLog implements DenyInterface, CreatedAtEntityInterface, BlameableEntityInterface, LogLevelInterface
{
    use DenyTrait;
    use CreatedAtEntityTrait;
    use BlameableEntityTrait;
    use LogLevelEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Groups(['vpnLog:public'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[Groups(['vpnLog:public'])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'vpnLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Device $device = null;

    #[Groups(['vpnLog:public'])]
    #[ORM\ManyToOne(targetEntity: DeviceEndpointDevice::class, inversedBy: 'vpnLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceEndpointDevice $endpointDevice = null;

    #[Groups(['vpnLog:admin'])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'vpnLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) 'VpnLog';
    }

    #[Groups(['vpnLog:public'])]
    public function getTarget(): User|Device|DeviceEndpointDevice|null
    {
        // User is limited via VpnLogController modifyQueryBuilder
        if ($this->getUser()) {
            return $this->getUser();
        }
        if ($this->getDevice()) {
            return $this->getDevice();
        }
        if ($this->getEndpointDevice()) {
            return $this->getEndpointDevice();
        }

        return null;
    }

    #[Groups(['special:export'])]
    public function getDeviceType(): DeviceType|null
    {
        if ($this->getDevice()) {
            return $this->getDevice()->getDeviceType();
        }

        if ($this->getEndpointDevice()) {
            return $this->getEndpointDevice()->getDevice()->getDeviceType();
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message)
    {
        $this->message = $message;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }

    public function getEndpointDevice(): ?DeviceEndpointDevice
    {
        return $this->endpointDevice;
    }

    public function setEndpointDevice(?DeviceEndpointDevice $endpointDevice)
    {
        $this->endpointDevice = $endpointDevice;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }
}
