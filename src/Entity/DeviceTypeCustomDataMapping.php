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
use App\Entity\Traits\CustomDataInterface;
use App\Entity\Traits\CustomDataTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Model\AuditableInterface;
use App\Validator\Constraints\CustomDataVariablePath;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DeviceTypeCustomDataMapping entity defines custom data mappings at the device type level.
 *
 * Each record represents a custom data field definition that specifies how to extract
 * and process custom data from communication payloads for devices of a specific type.
 */
#[ORM\Entity]
#[Assert\UniqueEntity(fields: ['deviceType', 'name'], groups: ['deviceTypeCustomDataMapping:common'], errorPath: 'name')]
#[Assert\UniqueEntity(fields: ['deviceType', 'variableName'], groups: ['deviceTypeCustomDataMapping:common'], errorPath: 'variableName')]
class DeviceTypeCustomDataMapping implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, CustomDataInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;
    use CustomDataTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Device type that owns this custom data mapping.
     */
    #[Groups(['deviceTypeCustomDataMapping:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceTypeCustomDataMapping:create'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'deviceTypeCustomDataMappings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    /**
     * Extract value from communication payload given $path (array keys) (one or multiple keys, supports dot notation)
     * Example: "cellularStatus.signal.rsrq".
     */
    #[Groups(['deviceTypeCustomDataMapping:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceTypeCustomDataMapping:common'])]
    #[CustomDataVariablePath(groups: ['deviceTypeCustomDataMapping:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $path = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
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

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path)
    {
        $this->path = $path;
    }
}
