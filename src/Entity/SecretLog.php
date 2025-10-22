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
use App\Enum\SecretOperation;
use Carve\ApiBundle\Attribute\Export\ExportEnumPrefix;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
class SecretLog implements DenyInterface, CreatedAtEntityInterface, BlameableEntityInterface
{
    use DenyTrait;
    use CreatedAtEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Type of operation performed on secret.
     */
    #[Groups(['secretLog:public'])]
    #[ExportEnumPrefix('enum.configuration.secretOperation.')]
    #[ORM\Column(type: Types::STRING, enumType: SecretOperation::class)]
    private ?SecretOperation $operation = null;

    #[Groups(['secretLog:public'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $previousSecretValue = null;

    #[Groups(['secretLog:previousSecretValue'])]
    #[SerializedName('previousSecretValue')]
    private ?string $decryptedPreviousSecretValue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $updatedSecretValue = null;

    #[Groups(['secretLog:updatedSecretValue'])]
    #[SerializedName('updatedSecretValue')]
    private ?string $decryptedUpdatedSecretValue = null;

    #[Groups(['secretLog:public'])]
    #[ORM\ManyToOne(targetEntity: DeviceSecret::class, inversedBy: 'secretLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceSecret $deviceSecret = null;

    #[Groups(['secretLog:public'])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'secretLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Device $device = null;

    #[Groups(['secretLog:public'])]
    #[ORM\ManyToOne(targetEntity: DeviceTypeSecret::class, inversedBy: 'secretLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceTypeSecret $deviceTypeSecret = null;

    #[Groups(['secretLog:public'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'secretLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceType $deviceType = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) 'SecretLog';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getOperation(): ?SecretOperation
    {
        return $this->operation;
    }

    public function setOperation(?SecretOperation $operation)
    {
        $this->operation = $operation;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message)
    {
        $this->message = $message;
    }

    public function getPreviousSecretValue(): ?string
    {
        return $this->previousSecretValue;
    }

    public function setPreviousSecretValue(?string $previousSecretValue)
    {
        $this->previousSecretValue = $previousSecretValue;
    }

    public function getUpdatedSecretValue(): ?string
    {
        return $this->updatedSecretValue;
    }

    public function setUpdatedSecretValue(?string $updatedSecretValue)
    {
        $this->updatedSecretValue = $updatedSecretValue;
    }

    public function getDeviceSecret(): ?DeviceSecret
    {
        return $this->deviceSecret;
    }

    public function setDeviceSecret(?DeviceSecret $deviceSecret)
    {
        $this->deviceSecret = $deviceSecret;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }

    public function getDeviceTypeSecret(): ?DeviceTypeSecret
    {
        return $this->deviceTypeSecret;
    }

    public function setDeviceTypeSecret(?DeviceTypeSecret $deviceTypeSecret)
    {
        $this->deviceTypeSecret = $deviceTypeSecret;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getDecryptedPreviousSecretValue(): ?string
    {
        return $this->decryptedPreviousSecretValue;
    }

    public function setDecryptedPreviousSecretValue(?string $decryptedPreviousSecretValue)
    {
        $this->decryptedPreviousSecretValue = $decryptedPreviousSecretValue;
    }

    public function getDecryptedUpdatedSecretValue(): ?string
    {
        return $this->decryptedUpdatedSecretValue;
    }

    public function setDecryptedUpdatedSecretValue(?string $decryptedUpdatedSecretValue)
    {
        $this->decryptedUpdatedSecretValue = $decryptedUpdatedSecretValue;
    }
}
