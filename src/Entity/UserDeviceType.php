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
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Enum\UserRole;
use App\Model\AuditableInterface;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[UniqueEntity('deviceType', 'user')]
class UserDeviceType implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Groups(['deviceAuthentication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: false, enumType: UserRole::class)]
    private ?UserRole $userRole = UserRole::SHOW;

    // Cascade persist added, because DeviceCommunicationAuthentication tests where not working without it
    #[Groups(['deviceAuthentication:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'userDeviceTypes', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userDeviceTypes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) 'UserDeviceType';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getUserRole(): ?UserRole
    {
        return $this->userRole;
    }

    public function setUserRole(?UserRole $userRole)
    {
        $this->userRole = $userRole;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
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
