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

use App\Entity\Traits\AccessTagsInterface;
use App\Entity\Traits\BlameableEntityInterface;
use App\Entity\Traits\BlameableEntityTrait;
use App\Entity\Traits\InjectedAccessTagsInterface;
use App\Entity\Traits\InjectedAccessTagsTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Enum\MasqueradeType;
use App\Enum\TemplateVersionType;
use App\Model\AuditableInterface;
use App\Validator\Constraints\TemplateVersion as TemplateVersionValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[TemplateVersionValidator(groups: ['templateVersion:common'])]
class TemplateVersion implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AccessTagsInterface, AuditableInterface, InjectedAccessTagsInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;
    use InjectedAccessTagsTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Template version type (staging or production).
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: TemplateVersionType::class)]
    private ?TemplateVersionType $type = null;

    /**
     * Device type.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'templateVersions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    /**
     * Name.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['templateVersion:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Description.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Device description.
     */
    #[Groups(['templateVersion:adminVpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deviceDescription = null;

    /**
     * Masquerade type (disabled, default or advanced).
     */
    #[Groups(['templateVersion:adminVpn', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: MasqueradeType::class, nullable: true)]
    private ?MasqueradeType $masqueradeType = null;

    /**
     * Config for primary feature.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Config::class, inversedBy: 'templates1')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Config $config1 = null;

    /**
     * Config for secondary feature.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Config::class, inversedBy: 'templates2')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Config $config2 = null;

    /**
     * Config for tertiary feature.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Config::class, inversedBy: 'templates3')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Config $config3 = null;

    /**
     * Firmware for primary feature.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Firmware::class, inversedBy: 'templates1')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Firmware $firmware1 = null;

    /**
     * Firmware for secondary feature.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Firmware::class, inversedBy: 'templates2')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Firmware $firmware2 = null;

    /**
     * Firmware for tertiary feature.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Firmware::class, inversedBy: 'templates3')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Firmware $firmware3 = null;

    /**
     * Size of subnet as CIDR stored as integer (i.e. /32 = 1, /30 = 4, /24 = 256).
     */
    #[Groups(['templateVersion:adminVpn', AuditableInterface::GROUP])]
    #[Assert\Range(min: 1, max: 32, groups: ['templateVersion:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $virtualSubnetCidr = null;

    /**
     * Masquerades.
     */
    #[Groups(['templateVersion:adminVpn'])]
    #[Assert\Valid(groups: ['templateVersion:common'])]
    #[ORM\OneToMany(mappedBy: 'templateVersion', targetEntity: TemplateVersionMasquerade::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $masquerades;

    /**
     * Endpoint devices.
     */
    #[Groups(['templateVersion:adminVpn'])]
    #[Assert\Valid(groups: ['templateVersion:common'])]
    #[ORM\OneToMany(mappedBy: 'templateVersion', targetEntity: TemplateVersionEndpointDevice::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $endpointDevices;

    /**
     * Access tags.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'templateVersions', targetEntity: AccessTag::class)]
    private Collection $accessTags;

    /**
     * Device labels.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'templateVersions', targetEntity: Label::class)]
    private Collection $deviceLabels;

    /**
     * Variables.
     */
    #[Groups(['templateVersion:public'])]
    #[Assert\Valid(groups: ['templateVersion:common'])]
    #[ORM\OneToMany(mappedBy: 'templateVersion', targetEntity: TemplateVersionVariable::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variables;

    /**
     * Template.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Template::class, inversedBy: 'templateVersions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Template $template = null;

    /**
     * Should primary firmware be reinstalled for connected devices?
     */
    private ?bool $reinstallFirmware1 = false;

    /**
     * Should secondary firmware be reinstalled for connected devices?
     */
    private ?bool $reinstallFirmware2 = false;

    /**
     * Should tertiary firmware be reinstalled for connected devices?
     */
    private ?bool $reinstallFirmware3 = false;

    /**
     * Should primary config be reinstalled for connected devices?
     */
    private ?bool $reinstallConfig1 = false;

    /**
     * Should secondary config be reinstalled for connected devices?
     */
    private ?bool $reinstallConfig2 = false;

    /**
     * Should tertiary config be reinstalled for connected devices?
     */
    private ?bool $reinstallConfig3 = false;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
            $accessTag->addTemplateVersion($this);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
            $accessTag->removeTemplateVersion($this);
        }
    }

    public function addDeviceLabel(Label $deviceLabel)
    {
        if (!$this->deviceLabels->contains($deviceLabel)) {
            $this->deviceLabels->add($deviceLabel);
            $deviceLabel->addTemplateVersion($this);
        }
    }

    public function removeDeviceLabel(Label $deviceLabel)
    {
        if ($this->deviceLabels->contains($deviceLabel)) {
            $this->deviceLabels->removeElement($deviceLabel);
            $deviceLabel->removeTemplateVersion($this);
        }
    }

    public function addVariable(TemplateVersionVariable $variable)
    {
        if (!$this->variables->contains($variable)) {
            $this->variables[] = $variable;
            $variable->setTemplateVersion($this);
        }
    }

    public function removeVariable(TemplateVersionVariable $variable)
    {
        if ($this->variables->removeElement($variable)) {
            if ($variable->getTemplateVersion() === $this) {
                $variable->setTemplateVersion(null);
            }
        }
    }

    public function addMasquerade(TemplateVersionMasquerade $masquerade)
    {
        if (!$this->masquerades->contains($masquerade)) {
            $this->masquerades[] = $masquerade;
            $masquerade->setTemplateVersion($this);
        }
    }

    public function removeMasquerade(TemplateVersionMasquerade $masquerade)
    {
        if ($this->masquerades->removeElement($masquerade)) {
            if ($masquerade->getTemplateVersion() === $this) {
                $masquerade->setTemplateVersion(null);
            }
        }
    }

    public function addEndpointDevice(TemplateVersionEndpointDevice $endpointDevice)
    {
        if (!$this->endpointDevices->contains($endpointDevice)) {
            $this->endpointDevices[] = $endpointDevice;
            $endpointDevice->setTemplateVersion($this);
        }
    }

    public function removeEndpointDevice(TemplateVersionEndpointDevice $endpointDevice)
    {
        if ($this->endpointDevices->removeElement($endpointDevice)) {
            if ($endpointDevice->getTemplateVersion() === $this) {
                $endpointDevice->setTemplateVersion(null);
            }
        }
    }

    public function __construct()
    {
        $this->accessTags = new ArrayCollection();
        $this->deviceLabels = new ArrayCollection();
        $this->endpointDevices = new ArrayCollection();
        $this->masquerades = new ArrayCollection();
        $this->variables = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getDeviceDescription(): ?string
    {
        return $this->deviceDescription;
    }

    public function setDeviceDescription(?string $deviceDescription)
    {
        $this->deviceDescription = $deviceDescription;
    }

    public function getConfig1(): ?Config
    {
        return $this->config1;
    }

    public function setConfig1(?Config $config1)
    {
        $this->config1 = $config1;
    }

    public function getConfig2(): ?Config
    {
        return $this->config2;
    }

    public function setConfig2(?Config $config2)
    {
        $this->config2 = $config2;
    }

    public function getConfig3(): ?Config
    {
        return $this->config3;
    }

    public function setConfig3(?Config $config3)
    {
        $this->config3 = $config3;
    }

    public function getFirmware1(): ?Firmware
    {
        return $this->firmware1;
    }

    public function setFirmware1(?Firmware $firmware1)
    {
        $this->firmware1 = $firmware1;
    }

    public function getFirmware2(): ?Firmware
    {
        return $this->firmware2;
    }

    public function setFirmware2(?Firmware $firmware2)
    {
        $this->firmware2 = $firmware2;
    }

    public function getFirmware3(): ?Firmware
    {
        return $this->firmware3;
    }

    public function setFirmware3(?Firmware $firmware3)
    {
        $this->firmware3 = $firmware3;
    }

    public function getMasquerades(): Collection
    {
        return $this->masquerades;
    }

    public function setMasquerades(Collection $masquerades)
    {
        $this->masquerades = $masquerades;
    }

    public function getEndpointDevices(): Collection
    {
        return $this->endpointDevices;
    }

    public function setEndpointDevices(Collection $endpointDevices)
    {
        $this->endpointDevices = $endpointDevices;
    }

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getVariables(): Collection
    {
        return $this->variables;
    }

    public function setVariables(Collection $variables)
    {
        $this->variables = $variables;
    }

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template)
    {
        $this->template = $template;
    }

    public function getMasqueradeType(): ?MasqueradeType
    {
        return $this->masqueradeType;
    }

    public function setMasqueradeType(?MasqueradeType $masqueradeType)
    {
        $this->masqueradeType = $masqueradeType;
    }

    public function getVirtualSubnetCidr(): ?int
    {
        return $this->virtualSubnetCidr;
    }

    public function setVirtualSubnetCidr(?int $virtualSubnetCidr)
    {
        $this->virtualSubnetCidr = $virtualSubnetCidr;
    }

    public function getType(): ?TemplateVersionType
    {
        return $this->type;
    }

    public function setType(?TemplateVersionType $type)
    {
        $this->type = $type;
    }

    public function getReinstallFirmware1(): ?bool
    {
        return $this->reinstallFirmware1;
    }

    public function setReinstallFirmware1(?bool $reinstallFirmware1)
    {
        $this->reinstallFirmware1 = $reinstallFirmware1;
    }

    public function getReinstallFirmware2(): ?bool
    {
        return $this->reinstallFirmware2;
    }

    public function setReinstallFirmware2(?bool $reinstallFirmware2)
    {
        $this->reinstallFirmware2 = $reinstallFirmware2;
    }

    public function getReinstallFirmware3(): ?bool
    {
        return $this->reinstallFirmware3;
    }

    public function setReinstallFirmware3(?bool $reinstallFirmware3)
    {
        $this->reinstallFirmware3 = $reinstallFirmware3;
    }

    public function getReinstallConfig1(): ?bool
    {
        return $this->reinstallConfig1;
    }

    public function setReinstallConfig1(?bool $reinstallConfig1)
    {
        $this->reinstallConfig1 = $reinstallConfig1;
    }

    public function getReinstallConfig2(): ?bool
    {
        return $this->reinstallConfig2;
    }

    public function setReinstallConfig2(?bool $reinstallConfig2)
    {
        $this->reinstallConfig2 = $reinstallConfig2;
    }

    public function getReinstallConfig3(): ?bool
    {
        return $this->reinstallConfig3;
    }

    public function setReinstallConfig3(?bool $reinstallConfig3)
    {
        $this->reinstallConfig3 = $reinstallConfig3;
    }

    public function getDeviceLabels(): Collection
    {
        return $this->deviceLabels;
    }

    public function setDeviceLabels(Collection $deviceLabels)
    {
        $this->deviceLabels = $deviceLabels;
    }
}
