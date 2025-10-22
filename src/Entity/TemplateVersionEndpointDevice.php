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
use App\Entity\Traits\VpnEndpointDeviceEntityTrait;
use App\Model\AuditableInterface;
use App\Validator\Constraints\EndpointDevice as EndpointDeviceValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[EndpointDeviceValidator(groups: ['templateVersion:common'])]
class TemplateVersionEndpointDevice implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use VpnEndpointDeviceEntityTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

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
     * Template version.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: TemplateVersion::class, inversedBy: 'endpointDevices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TemplateVersion $templateVersion = null;

    /**
     * Access tags.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'templateVersionEndpointDevices', targetEntity: AccessTag::class)]
    private Collection $accessTags;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
            $accessTag->addTemplateVersionEndpointDevice($this);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
            $accessTag->removeTemplateVersionEndpointDevice($this);
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

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getTemplateVersion(): ?TemplateVersion
    {
        return $this->templateVersion;
    }

    public function setTemplateVersion(?TemplateVersion $templateVersion)
    {
        $this->templateVersion = $templateVersion;
    }
}
