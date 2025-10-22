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

use App\Entity\Traits\LogLevelEntityTrait;
use App\Entity\Traits\LogLevelInterface;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Enum\Feature;
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
#[ORM\Index(name: 'idx_createdAt', columns: ['created_at'])]
#[ORM\Index(name: 'idx_createdAt_id', columns: ['created_at', 'id'])]
#[ORM\Index(name: 'idx_logLevel', columns: ['log_level'])]
class ConfigLog implements DenyInterface, TimestampableEntityInterface, LogLevelInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use LogLevelEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Defines config log feature. They are dynamically defined in connected device type.
     */
    #[Groups(['configLog:public'])]
    #[ORM\Column(type: Types::STRING, enumType: Feature::class)]
    private ?Feature $feature = null;

    #[Assert\Length(max: 255)]
    #[Groups(['configLog:public'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $md5 = null;

    /**
     * Access tags.
     */
    #[Groups(['configLog:public'])]
    #[ORM\ManyToMany(targetEntity: AccessTag::class)]
    private Collection $accessTags;

    #[Groups(['configLog:public'])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'configLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Device $device = null;

    /**
     * Device type.
     */
    #[Groups(['configLog:public', 'deviceType:identification'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceType $deviceType = null;

    #[ORM\OneToOne(targetEntity: ConfigLogContent::class, mappedBy: 'configLog')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?ConfigLogContent $configLogContent = null;

    #[Groups(['configLog:public'])]
    #[ORM\ManyToOne(targetEntity: CommunicationLog::class, inversedBy: 'configLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CommunicationLog $communicationLog;

    #[Groups(['configLog:content'])]
    #[SerializedName('content')]
    private ?string $decryptedContent = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) 'ConfigLog';
    }

    #[Groups(['special:export'])]
    public function getFeatureName(): string|null
    {
        switch ($this->getFeature()) {
            case Feature::PRIMARY:
                return $this->getDeviceType()?->getNameConfig1();
            case Feature::SECONDARY:
                return $this->getDeviceType()?->getNameConfig2();
            case Feature::TERTIARY:
                return $this->getDeviceType()?->getNameConfig3();
        }

        return null;
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

    public function getMd5(): ?string
    {
        return $this->md5;
    }

    public function setMd5(?string $md5)
    {
        $this->md5 = $md5;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }

    public function getCommunicationLog(): ?CommunicationLog
    {
        return $this->communicationLog;
    }

    public function setCommunicationLog(?CommunicationLog $communicationLog)
    {
        $this->communicationLog = $communicationLog;
    }

    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    public function setFeature(?Feature $feature)
    {
        $this->feature = $feature;
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

    public function getConfigLogContent(): ?ConfigLogContent
    {
        return $this->configLogContent;
    }

    public function setConfigLogContent(?ConfigLogContent $configLogContent)
    {
        $this->configLogContent = $configLogContent;
    }
}
