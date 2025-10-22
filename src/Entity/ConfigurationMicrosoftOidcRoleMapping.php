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

use App\Enum\MicrosoftOidcRole;
use App\Model\AuditableInterface;
use App\Validator\Constraints\ConfigurationMicrosoftOidcRoleMapping as ConfigurationMicrosoftOidcRoleMappingValidator;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ConfigurationMicrosoftOidcRoleMappingValidator(groups: ['configuration:sso'])]
class ConfigurationMicrosoftOidcRoleMapping implements AuditableInterface
{
    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Microsoft role name.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:sso'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $roleName = null;

    /**
     * User role.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:sso'])]
    #[ORM\Column(type: Types::STRING, enumType: MicrosoftOidcRole::class)]
    private ?MicrosoftOidcRole $microsoftOidcRole = null;

    /**
     * Allow user with VPN or device management permissions to manage endpoint devices.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleVpnEndpointDevices = false;

    /**
     * Access tags for non-admin users.
     */
    #[Groups(['configuration:sso', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'microsoftOidcRoleMappings', targetEntity: AccessTag::class)]
    private Collection $accessTags;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Configuration::class, inversedBy: 'microsoftOidcRoleMappings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Configuration $configuration = null;

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
            $accessTag->addMicrosoftOidcRoleMapping($this);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
            $accessTag->removeMicrosoftOidcRoleMapping($this);
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

    public function getRoleName(): ?string
    {
        return $this->roleName;
    }

    public function setRoleName(?string $roleName)
    {
        $this->roleName = $roleName;
    }

    public function getMicrosoftOidcRole(): ?MicrosoftOidcRole
    {
        return $this->microsoftOidcRole;
    }

    public function setMicrosoftOidcRole(?MicrosoftOidcRole $microsoftOidcRole)
    {
        $this->microsoftOidcRole = $microsoftOidcRole;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getRoleVpnEndpointDevices(): ?bool
    {
        return $this->roleVpnEndpointDevices;
    }

    public function setRoleVpnEndpointDevices(?bool $roleVpnEndpointDevices)
    {
        $this->roleVpnEndpointDevices = $roleVpnEndpointDevices;
    }
}
