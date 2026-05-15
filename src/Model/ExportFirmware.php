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

namespace App\Model;

use App\Entity\DeviceType;
use App\Entity\DeviceTypeHardware;
use App\Entity\Firmware;
use App\Entity\User;
use App\Enum\Feature;
use Carve\ApiBundle\Serializer\Normalizer\ExportEnumNormalizer;
use Symfony\Component\Serializer\Annotation\Groups;

class ExportFirmware
{
    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?int $id = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?DeviceType $deviceType = null;

    /**
     * Translated enum feature name.
     */
    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $feature = null;

    /**
     * Translated enum source type.
     */
    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $sourceType = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $name = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?bool $enableHardwareFiles = false;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $md5 = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $filename = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $filepath = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $externalUrl = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $uuid = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $version = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?\DateTime $createdAt = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?\DateTime $updatedAt = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?User $createdBy = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?User $updatedBy = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $representation = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?string $downloadUrl = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?DeviceTypeHardware $hardware = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?Firmware $requiredFirmware = null;

    #[Groups([ExportEnumNormalizer::EXPORT_GROUP])]
    private ?array $deny = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getFeature(): ?string
    {
        return $this->feature;
    }

    public function setFeature(?string $feature)
    {
        $this->feature = $feature;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType)
    {
        $this->sourceType = $sourceType;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getEnableHardwareFiles(): ?bool
    {
        return $this->enableHardwareFiles;
    }

    public function setEnableHardwareFiles(?bool $enableHardwareFiles)
    {
        $this->enableHardwareFiles = $enableHardwareFiles;
    }

    public function getMd5(): ?string
    {
        return $this->md5;
    }

    public function setMd5(?string $md5)
    {
        $this->md5 = $md5;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename)
    {
        $this->filename = $filename;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function setFilepath(?string $filepath)
    {
        $this->filepath = $filepath;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(?string $externalUrl)
    {
        $this->externalUrl = $externalUrl;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version)
    {
        $this->version = $version;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy)
    {
        $this->createdBy = $createdBy;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }

    public function getRepresentation(): ?string
    {
        return $this->representation;
    }

    public function setRepresentation(?string $representation)
    {
        $this->representation = $representation;
    }

    public function getDownloadUrl(): ?string
    {
        return $this->downloadUrl;
    }

    public function setDownloadUrl(?string $downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;
    }

    public function getHardware(): ?DeviceTypeHardware
    {
        return $this->hardware;
    }

    public function setHardware(?DeviceTypeHardware $hardware)
    {
        $this->hardware = $hardware;
    }

    public function getDeny(): ?array
    {
        return $this->deny;
    }

    public function setDeny(?array $deny)
    {
        $this->deny = $deny;
    }

    public function getRequiredFirmware(): ?Firmware
    {
        return $this->requiredFirmware;
    }

    public function setRequiredFirmware(?Firmware $requiredFirmware)
    {
        $this->requiredFirmware = $requiredFirmware;
    }
}
