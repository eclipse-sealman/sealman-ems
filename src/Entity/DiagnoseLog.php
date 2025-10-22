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
#[ORM\Index(name: 'idx_logLevel', columns: ['log_level'])]
class DiagnoseLog implements DenyInterface, CreatedAtEntityInterface, LogLevelInterface
{
    use DenyTrait;
    use CreatedAtEntityTrait;
    use LogLevelEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Access tags.
     */
    #[Groups(['diagnoseLog:public'])]
    #[ORM\ManyToMany(targetEntity: AccessTag::class)]
    private Collection $accessTags;

    #[Groups(['diagnoseLog:public'])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'diagnoseLogs')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Device $device = null;

    /**
     * Device type.
     */
    #[Groups(['diagnoseLog:public', 'deviceType:identification'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeviceType $deviceType = null;

    #[ORM\OneToOne(targetEntity: DiagnoseLogContent::class, mappedBy: 'diagnoseLog')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?DiagnoseLogContent $diagnoseLogContent = null;

    #[Groups(['diagnoseLog:content'])]
    #[SerializedName('content')]
    private ?string $decryptedContent = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) ('DiagnoseLog');
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

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
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

    public function getDiagnoseLogContent(): ?DiagnoseLogContent
    {
        return $this->diagnoseLogContent;
    }

    public function setDiagnoseLogContent(?DiagnoseLogContent $diagnoseLogContent)
    {
        $this->diagnoseLogContent = $diagnoseLogContent;
    }
}
