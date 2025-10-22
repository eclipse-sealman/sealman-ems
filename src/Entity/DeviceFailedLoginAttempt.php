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
use App\Enum\AuthenticationMethod;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Index(name: 'idx_createdAt', columns: ['created_at'])]
class DeviceFailedLoginAttempt implements CreatedAtEntityInterface
{
    use CreatedAtEntityTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Remote host.
     */
    #[Groups(['deviceFailedLoginAttempt:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $remoteHost = null;

    /**
     * Requested url.
     */
    #[Groups(['deviceFailedLoginAttempt:public'])]
    #[ORM\Column(type: Types::STRING, length: 2000, nullable: true)]
    private ?string $url = null;

    /**
     * User identifier provided.
     */
    #[Groups(['deviceFailedLoginAttempt:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $userIdentifier = null;

    /**
     * Requested device type.
     */
    #[Groups(['deviceFailedLoginAttempt:public'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceType $deviceType = null;

    /**
     * Authentication method.
     */
    #[Groups(['deviceFailedLoginAttempt:public'])]
    #[ORM\Column(type: Types::STRING, enumType: AuthenticationMethod::class)]
    private ?AuthenticationMethod $authenticationMethod = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getUserIdentifier();
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url)
    {
        $this->url = $url;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getAuthenticationMethod(): ?AuthenticationMethod
    {
        return $this->authenticationMethod;
    }

    public function setAuthenticationMethod(?AuthenticationMethod $authenticationMethod)
    {
        $this->authenticationMethod = $authenticationMethod;
    }
}
