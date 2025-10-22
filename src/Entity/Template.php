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
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Model\AuditableInterface;
use App\Validator\Constraints\AvailableDeviceType;
use App\Validator\Constraints\Template as TemplateValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[TemplateValidator(groups: ['template:common'])]
class Template implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Name.
     */
    #[Groups(['template:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['template:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Device type.
     */
    #[Groups(['template:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['template:create'])]
    #[AvailableDeviceType(groups: ['template:create'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'templates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    /**
     * Connected devices.
     */
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Device::class)]
    private Collection $devices;

    /**
     * Connected template versions.
     */
    #[Groups(['template:version'])]
    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateVersion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $templateVersions;

    /**
     * Production template.
     */
    #[Groups(['device:public', 'template:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: TemplateVersion::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TemplateVersion $productionTemplate = null;

    /**
     * Staging template.
     */
    #[Groups(['device:public', 'template:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: TemplateVersion::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TemplateVersion $stagingTemplate = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function __construct()
    {
        $this->devices = new ArrayCollection();
        $this->templateVersions = new ArrayCollection();
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

    public function getDevices(): Collection
    {
        return $this->devices;
    }

    public function setDevices(Collection $devices)
    {
        $this->devices = $devices;
    }

    public function getTemplateVersions(): Collection
    {
        return $this->templateVersions;
    }

    public function setTemplateVersions(Collection $templateVersions)
    {
        $this->templateVersions = $templateVersions;
    }

    public function getProductionTemplate(): ?TemplateVersion
    {
        return $this->productionTemplate;
    }

    public function setProductionTemplate(?TemplateVersion $productionTemplate)
    {
        $this->productionTemplate = $productionTemplate;
    }

    public function getStagingTemplate(): ?TemplateVersion
    {
        return $this->stagingTemplate;
    }

    public function setStagingTemplate(?TemplateVersion $stagingTemplate)
    {
        $this->stagingTemplate = $stagingTemplate;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }
}
