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
use App\Entity\Traits\TemplateComponentInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use App\Model\AuditableInterface;
use App\Validator\Constraints\AvailableDeviceType;
use App\Validator\Constraints\Config as ConfigValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ConfigValidator(groups: ['config:common'])]
class Config implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, TemplateComponentInterface, AuditableInterface
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
    #[Groups(['config:public', 'templateVersion:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['config:create'])]
    #[AvailableDeviceType(groups: ['config:create'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'configs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    /**
     * Defines config feature. They are dynamically defined in connected device type.
     */
    #[Groups(['config:public', 'templateVersion:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['config:create'])]
    #[ORM\Column(type: Types::STRING, enumType: Feature::class)]
    private ?Feature $feature = null;

    /**
     * Name.
     */
    #[Groups(['config:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['config:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Generator.
     */
    #[Groups(['config:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['config:common'])]
    #[ORM\Column(type: Types::STRING, enumType: ConfigGenerator::class)]
    private ?ConfigGenerator $generator = null;

    /**
     * Content.
     */
    #[Groups(['config:public', 'templateVersion:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['config:common'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    /**
     * UUID.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, unique: true)]
    private ?string $uuid = null;

    /**
     * Connected templates for primary feature.
     */
    #[Groups(['config:public'])]
    #[ORM\OneToMany(mappedBy: 'config1', targetEntity: TemplateVersion::class)]
    private Collection $templates1;

    /**
     * Connected templates for secondary feature.
     */
    #[Groups(['config:public'])]
    #[ORM\OneToMany(mappedBy: 'config2', targetEntity: TemplateVersion::class)]
    private Collection $templates2;

    /**
     * Connected templates for tertiary feature.
     */
    #[Groups(['config:public'])]
    #[ORM\OneToMany(mappedBy: 'config3', targetEntity: TemplateVersion::class)]
    private Collection $templates3;

    /**
     * Should configs be reinstalled for connected devices?
     */
    private ?bool $reinstallConfig = false;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getGenerator(): ?ConfigGenerator
    {
        return $this->generator;
    }

    public function setGenerator(?ConfigGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content)
    {
        $this->content = $content;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid)
    {
        $this->uuid = $uuid;
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

    public function getReinstallConfig(): ?bool
    {
        return $this->reinstallConfig;
    }

    public function setReinstallConfig(?bool $reinstallConfig)
    {
        $this->reinstallConfig = $reinstallConfig;
    }
}
