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
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Validator\Constraints\SecretValue as SecretValueValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
#[SecretValueValidator(groups: ['deviceSecret:common'])]
class DeviceSecret implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Secret value - encoded by encoding algorythm.
     */
    #[Assert\NotBlank(groups: ['device:common'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $secretValue = null;

    #[Groups(['deviceSecret:secret'])]
    #[SerializedName('secretValue')]
    private ?string $decryptedSecretValue = null;

    /**
     * Secret variable encoded variable list.
     */
    // Collection based on VariableValueModel
    #[Groups(['deviceSecret:secret'])]
    private Collection $encodedVariables;

    /**
     * Secret value renewed at.
     */
    #[Groups(['deviceSecret:public'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $renewedAt = null;

    /**
     * Force renewal on next device communication.
     */
    #[Groups(['deviceSecret:public'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $forceRenewal = false;

    /**
     * Device.
     */
    #[Groups(['deviceSecret:public'])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'deviceSecrets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Device $device = null;

    /**
     * Device type secret.
     */
    #[Groups(['deviceSecret:public'])]
    #[ORM\ManyToOne(targetEntity: DeviceTypeSecret::class, inversedBy: 'deviceSecrets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceTypeSecret $deviceTypeSecret = null;

    /**
     * Secret logs.
     */
    #[ORM\OneToMany(mappedBy: 'deviceSecret', targetEntity: SecretLog::class)]
    private Collection $secretLogs;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return $this->getDeviceTypeSecret()->getRepresentation();
    }

    public function getSecretName(): string
    {
        return $this->getDeviceTypeSecret()->getName();
    }

    public function __construct()
    {
        $this->secretLogs = new ArrayCollection();
        $this->encodedVariables = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getSecretValue(): ?string
    {
        return $this->secretValue;
    }

    public function setSecretValue(?string $secretValue)
    {
        $this->secretValue = $secretValue;
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

    public function getSecretLogs(): Collection
    {
        return $this->secretLogs;
    }

    public function setSecretLogs(Collection $secretLogs)
    {
        $this->secretLogs = $secretLogs;
    }

    public function getDecryptedSecretValue(): ?string
    {
        return $this->decryptedSecretValue;
    }

    public function setDecryptedSecretValue(?string $decryptedSecretValue)
    {
        $this->decryptedSecretValue = $decryptedSecretValue;
    }

    public function getEncodedVariables(): Collection
    {
        return $this->encodedVariables;
    }

    public function setEncodedVariables(Collection $encodedVariables)
    {
        $this->encodedVariables = $encodedVariables;
    }

    public function getRenewedAt(): ?\DateTime
    {
        return $this->renewedAt;
    }

    public function setRenewedAt(?\DateTime $renewedAt)
    {
        $this->renewedAt = $renewedAt;
    }

    public function getForceRenewal(): ?bool
    {
        return $this->forceRenewal;
    }

    public function setForceRenewal(?bool $forceRenewal)
    {
        $this->forceRenewal = $forceRenewal;
    }
}
