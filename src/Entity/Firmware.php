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
use App\Entity\Traits\TemplateComponentInterface;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Enum\Feature;
use App\Enum\SourceType;
use App\Model\AuditableInterface;
use App\Model\UploadInterface;
use App\Validator\Constraints\AvailableDeviceType;
use App\Validator\Constraints\Firmware as FirmwareValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[FirmwareValidator(groups: ['firmware:common'])]
class Firmware implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, UploadInterface, TemplateComponentInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Device type.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['firmware:create'])]
    #[AvailableDeviceType(groups: ['firmware:create'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'firmwares')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    /**
     * Defines firmware feature. They are dynamically defined in connected device type.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['firmware:create'])]
    #[ORM\Column(type: Types::STRING, enumType: Feature::class)]
    private ?Feature $feature = null;

    /**
     * Source type (upload or external url).
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['firmware:common'])]
    #[ORM\Column(type: Types::STRING, enumType: SourceType::class)]
    private ?SourceType $sourceType = null;

    /**
     * Name.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['firmware:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * MD5 hash.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $md5 = null;

    /**
     * Filename.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $filename = null;

    /**
     * Filepath. Available only for uploaded firmwares.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $filepath = null;

    /**
     * External URL. Available only for external URL firmwares.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[Assert\Url(groups: ['firmware:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $externalUrl = null;

    /**
     * UUID.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, unique: true)]
    private ?string $uuid = null;

    /**
     * Legacy UUID.
     */
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $legacyUuid = null;

    /**
     * Secret for downloading firmware by device.
     */
    #[ORM\Column(type: Types::STRING)]
    private ?string $secret = null;

    /**
     * Version.
     */
    #[Groups(['firmware:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['firmware:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $version = null;

    /**
     * Connected templates for primary feature.
     */
    #[ORM\OneToMany(mappedBy: 'firmware1', targetEntity: TemplateVersion::class)]
    private Collection $templates1;

    /**
     * Connected templates for secondary feature.
     */
    #[ORM\OneToMany(mappedBy: 'firmware2', targetEntity: TemplateVersion::class)]
    private Collection $templates2;

    /**
     * Connected templates for tertiary feature.
     */
    #[ORM\OneToMany(mappedBy: 'firmware3', targetEntity: TemplateVersion::class)]
    private Collection $templates3;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    #[Groups(['firmware:public'])]
    public function getDownloadUrl(): ?string
    {
        if (SourceType::EXTERNAL_URL == $this->getSourceType()) {
            return $this->getExternalUrl();
        }

        if (SourceType::UPLOAD == $this->getSourceType()) {
            return '/web/api/download/firmware/'.$this->getUploadDirPart().'/'.$this->getFilename();
        }

        return null;
    }

    public function getUploadFields(): array
    {
        return [
            'filepath',
        ];
    }

    public function getUploadDirPart(): string
    {
        return $this->getDeviceType()->getSlug().'/'.$this->getUuid();
    }

    public function getUploadDir(string $field): ?string
    {
        return '../private/firmware/'.$this->getUploadDirPart().'/';
    }

    public function __construct()
    {
        $this->templates1 = new ArrayCollection();
        $this->templates2 = new ArrayCollection();
        $this->templates3 = new ArrayCollection();
    }

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

    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    public function setFeature(?Feature $feature)
    {
        $this->feature = $feature;
    }

    public function getSourceType(): ?SourceType
    {
        return $this->sourceType;
    }

    public function setSourceType(?SourceType $sourceType)
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

    public function getTemplates1(): Collection
    {
        return $this->templates1;
    }

    public function setTemplates1(Collection $templates1)
    {
        $this->templates1 = $templates1;
    }

    public function getTemplates2(): Collection
    {
        return $this->templates2;
    }

    public function setTemplates2(Collection $templates2)
    {
        $this->templates2 = $templates2;
    }

    public function getTemplates3(): Collection
    {
        return $this->templates3;
    }

    public function setTemplates3(Collection $templates3)
    {
        $this->templates3 = $templates3;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret)
    {
        $this->secret = $secret;
    }

    public function getLegacyUuid(): ?string
    {
        return $this->legacyUuid;
    }

    public function setLegacyUuid(?string $legacyUuid)
    {
        $this->legacyUuid = $legacyUuid;
    }
}
