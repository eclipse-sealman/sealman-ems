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

use App\Entity\Traits\CommunicationEntityInterface;
use App\Entity\Traits\CommunicationEntityTrait;
use App\Entity\Traits\CreatedAtEntityInterface;
use App\Entity\Traits\CreatedAtEntityTrait;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Entity\Traits\FirmwareStatusEntityTrait;
use App\Entity\Traits\GsmEntityInterface;
use App\Entity\Traits\GsmEntityTrait;
use App\Entity\Traits\LogLevelEntityTrait;
use App\Entity\Traits\LogLevelInterface;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

#[ORM\Entity]
#[ORM\Index(name: 'idx_createdAt', columns: ['created_at'])]
#[ORM\Index(name: 'idx_createdAt_id', columns: ['created_at', 'id'])]
#[ORM\Index(name: 'idx_serialNumber', columns: ['serial_number'])]
#[ORM\Index(name: 'idx_logLevel', columns: ['log_level'])]
class CommunicationLog implements DenyInterface, CreatedAtEntityInterface, LogLevelInterface, GsmEntityInterface, FirmwareStatusEntityInterface, CommunicationEntityInterface
{
    use DenyTrait;
    use CreatedAtEntityTrait;
    use GsmEntityTrait;
    use CommunicationEntityTrait;
    use FirmwareStatusEntityTrait;
    use LogLevelEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Groups(['communicationLog:public'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    /**
     * Access tags.
     */
    #[Groups(['communicationLog:public'])]
    #[ORM\ManyToMany(targetEntity: AccessTag::class)]
    private Collection $accessTags;

    #[Groups(['communicationLog:public'])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'communicationLogs', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Device $device = null;

    /**
     * Device type.
     */
    #[Groups(['communicationLog:public', 'deviceType:identification'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceType $deviceType = null;

    #[ORM\OneToMany(mappedBy: 'communicationLog', targetEntity: ConfigLog::class)]
    private Collection $configLogs;

    #[ORM\OneToOne(targetEntity: CommunicationLogContent::class, mappedBy: 'communicationLog')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?CommunicationLogContent $communicationLogContent = null;

    /**
     * Decrypted content.
     * Helper field used to provide decrypted content.
     */
    #[Groups(['communicationLog:content'])]
    #[SerializedName('content')]
    private ?string $decryptedContent = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) ('CommunicationLog');
    }

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
        }
    }

    public function __construct()
    {
        $this->configLogs = new ArrayCollection();
        $this->accessTags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
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

    public function getConfigLogs(): Collection
    {
        return $this->configLogs;
    }

    public function setConfigLogs(Collection $configLogs)
    {
        $this->configLogs = $configLogs;
    }

    public function getDecryptedContent(): ?string
    {
        return $this->decryptedContent;
    }

    public function setDecryptedContent(?string $decryptedContent)
    {
        $this->decryptedContent = $decryptedContent;
    }

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getCommunicationLogContent(): ?CommunicationLogContent
    {
        return $this->communicationLogContent;
    }

    public function setCommunicationLogContent(?CommunicationLogContent $communicationLogContent)
    {
        $this->communicationLogContent = $communicationLogContent;
    }
}
