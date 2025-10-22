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

use App\Model\AuditableInterface;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Carve\ApiBundle\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[UniqueEntity('name', groups: ['label:common'])]
class Label implements AuditableInterface
{
    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Groups(['label:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['label:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    #[ORM\ManyToMany(mappedBy: 'labels', targetEntity: Device::class)]
    private Collection $devices;

    #[ORM\ManyToMany(mappedBy: 'deviceLabels', targetEntity: TemplateVersion::class)]
    private Collection $templateVersions;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function addDevice(Device $device)
    {
        if (!$this->devices->contains($device)) {
            $this->devices->add($device);
            $device->addLabel($this);
        }
    }

    public function removeDevice(Device $device)
    {
        if ($this->devices->contains($device)) {
            $this->devices->removeElement($device);
            $device->removeLabel($this);
        }
    }

    public function addTemplateVersion(TemplateVersion $templateVersion)
    {
        if (!$this->templateVersions->contains($templateVersion)) {
            $this->templateVersions->add($templateVersion);
            $templateVersion->addDeviceLabel($this);
        }
    }

    public function removeTemplateVersion(TemplateVersion $templateVersion)
    {
        if ($this->templateVersions->contains($templateVersion)) {
            $this->templateVersions->removeElement($templateVersion);
            $templateVersion->removeDeviceLabel($this);
        }
    }

    public function __construct()
    {
        $this->templateVersions = new ArrayCollection();
        $this->devices = new ArrayCollection();
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
}
