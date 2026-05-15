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
use App\Enum\SourceType;
use App\Model\AuditableInterface;
use App\Model\UploadInterface;
use App\Validator\Constraints\FirmwareHardwareFile as FirmwareHardwareFileValidator;
use App\Validator\Constraints\FirmwareSourceType;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[FirmwareSourceType(groups: ['firmwareHardwareFile:common'])]
#[FirmwareHardwareFileValidator(groups: ['firmwareHardwareFile:common'])]
#[Assert\UniqueEntity(fields: ['firmware', 'hardware'], groups: ['firmwareHardwareFile:common'], errorPath: 'hardware')]
class FirmwareHardwareFile implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, UploadInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Source type (upload or external url).
     */
    #[Groups(['firmwareHardwareFile:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['firmwareHardwareFile:common'])]
    #[ORM\Column(type: Types::STRING, enumType: SourceType::class)]
    private ?SourceType $sourceType = null;

    /**
     * MD5 hash.
     */
    #[Groups(['firmwareHardwareFile:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $md5 = null;

    /**
     * Filename.
     */
    #[Groups(['firmwareHardwareFile:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $filename = null;

    /**
     * Filepath. Available only for uploaded firmwares.
     */
    #[Groups(['firmwareHardwareFile:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $filepath = null;

    /**
     * External URL. Available only for external URL firmwares.
     */
    #[Groups(['firmwareHardwareFile:public', AuditableInterface::GROUP])]
    #[Assert\Url(groups: ['firmwareHardwareFile:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $externalUrl = null;

    /**
     * Device type hardware.
     */
    #[Groups(['firmwareHardwareFile:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['firmwareHardwareFile:common'])]
    #[ORM\ManyToOne(targetEntity: DeviceTypeHardware::class, inversedBy: 'firmwareHardwareFiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DeviceTypeHardware $hardware = null;

    /**
     * Firmware.
     */
    #[Groups(['firmwareHardwareFile:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['firmwareHardwareFile:create'])]
    #[ORM\ManyToOne(targetEntity: Firmware::class, inversedBy: 'hardwareFiles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Firmware $firmware = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getHardware()?->getRepresentation() ?? 'Firmware hardware file';
    }

    #[Groups(['firmwareHardwareFile:public'])]
    public function getDownloadUrl(): ?string
    {
        if (SourceType::EXTERNAL_URL == $this->getSourceType()) {
            return $this->getExternalUrl();
        }

        if (SourceType::UPLOAD == $this->getSourceType()) {
            return '/web/api/download/firmwarehardwarefile/'.$this->getUploadDirPart().'/'.$this->getFilename();
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
        return $this->getFirmware()->getUploadDirPart();
    }

    public function getUploadDir(string $field): ?string
    {
        return '../private/firmwarehardwarefile/'.$this->getUploadDirPart().'/';
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

    public function getHardware(): ?DeviceTypeHardware
    {
        return $this->hardware;
    }

    public function setHardware(?DeviceTypeHardware $hardware)
    {
        $this->hardware = $hardware;
    }

    public function getFirmware(): ?Firmware
    {
        return $this->firmware;
    }

    public function setFirmware(?Firmware $firmware)
    {
        $this->firmware = $firmware;
    }

    public function getSourceType(): ?SourceType
    {
        return $this->sourceType;
    }

    public function setSourceType(?SourceType $sourceType)
    {
        $this->sourceType = $sourceType;
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
}
