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

use App\Enum\RadiusUserRole;
use App\Model\AuditableInterface;
use App\Validator\Constraints\ConfigurationRadiusWelotecGroupMapping as ConfigurationRadiusWelotecGroupMappingValidator;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ConfigurationRadiusWelotecGroupMappingValidator(groups: ['configuration:radius'])]
class ConfigurationRadiusWelotecGroupMapping implements AuditableInterface
{
    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Group name.
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:radius'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Role (Admin, Device management permissions, VPN permissions).
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['configuration:radius'])]
    #[ORM\Column(type: Types::STRING, enumType: RadiusUserRole::class)]
    private ?RadiusUserRole $radiusUserRole = null;

    /**
     * Allow user with VPN or device management permissions to manage endpoint devices.
     */
    #[Groups(['configuration:radius', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $roleVpnEndpointDevices = false;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Configuration::class, inversedBy: 'radiusWelotecGroupMappings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Configuration $configuration = null;

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

    public function getRadiusUserRole(): ?RadiusUserRole
    {
        return $this->radiusUserRole;
    }

    public function setRadiusUserRole(?RadiusUserRole $radiusUserRole)
    {
        $this->radiusUserRole = $radiusUserRole;
    }

    public function getConfiguration(): ?Configuration
    {
        return $this->configuration;
    }

    public function setConfiguration(?Configuration $configuration)
    {
        $this->configuration = $configuration;
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
