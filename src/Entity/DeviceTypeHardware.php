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
use App\Model\AuditableInterface;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[Assert\UniqueEntity(fields: ['deviceType', 'name'], groups: ['deviceTypeHardware:common'], errorPath: 'name')]
#[Assert\UniqueEntity(fields: ['deviceType', 'hardwareVersion'], groups: ['deviceTypeHardware:common'], errorPath: 'hardwareVersion')]
class DeviceTypeHardware implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Human friendly name of the hardware.
     */
    #[Groups(['deviceTypeHardware:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceTypeHardware:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Hardware version.
     */
    #[Groups(['deviceTypeHardware:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceTypeHardware:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $hardwareVersion = null;

    /**
     * Device type.
     */
    #[Groups(['deviceTypeHardware:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'deviceTypeHardwares')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    /**
     * Firmware hardware files.
     */
    #[ORM\OneToMany(mappedBy: 'hardware', targetEntity: FirmwareHardwareFile::class)]
    private Collection $firmwareHardwareFiles;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function __construct()
    {
        $this->firmwareHardwareFiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getHardwareVersion(): ?string
    {
        return $this->hardwareVersion;
    }

    public function setHardwareVersion(?string $hardwareVersion)
    {
        $this->hardwareVersion = $hardwareVersion;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getFirmwareHardwareFiles(): Collection
    {
        return $this->firmwareHardwareFiles;
    }

    public function setFirmwareHardwareFiles(Collection $firmwareHardwareFiles)
    {
        $this->firmwareHardwareFiles = $firmwareHardwareFiles;
    }
}
