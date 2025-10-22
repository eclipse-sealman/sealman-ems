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

use App\Entity\Traits\CreatedAtEntityInterface;
use App\Entity\Traits\CreatedAtEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Index(name: 'idx_createdAt', columns: ['created_at'])]
class UserLoginAttempt implements CreatedAtEntityInterface
{
    use CreatedAtEntityTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Is login valid (successful)?.
     */
    #[Groups(['userLoginAttempt:public'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $loginValid = false;

    /**
     * Is TOTP valid?.
     */
    #[Groups(['userLoginAttempt:public'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $totpValid = false;

    /**
     * Remote host.
     */
    #[Groups(['userLoginAttempt:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $remoteHost = null;

    /**
     * User name provided.
     */
    #[Groups(['userLoginAttempt:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $username = null;

    /**
     * User object (if found).
     */
    #[Groups(['userLoginAttempt:public'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getUsername();
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

    public function getRemoteHost(): ?string
    {
        return $this->remoteHost;
    }

    public function setRemoteHost(?string $remoteHost)
    {
        $this->remoteHost = $remoteHost;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username)
    {
        $this->username = $username;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function getLoginValid(): ?bool
    {
        return $this->loginValid;
    }

    public function setLoginValid(?bool $loginValid)
    {
        $this->loginValid = $loginValid;
    }

    public function getTotpValid(): ?bool
    {
        return $this->totpValid;
    }

    public function setTotpValid(?bool $totpValid)
    {
        $this->totpValid = $totpValid;
    }
}
