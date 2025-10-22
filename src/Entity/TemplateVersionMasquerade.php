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
use App\Validator\Constraints\Masquerade as MasqueradeValidator;
use App\Validator\Constraints\Subnet;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[MasqueradeValidator(groups: ['templateVersion:common'])]
class TemplateVersionMasquerade implements AuditableInterface
{
    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Subnet to be masqueraded using firewall e.g. iptables as CIDR subnet i.e. 172.56.0.0/16.
     */
    #[Groups(['templateVersion:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['templateVersion:common'])]
    #[Subnet(groups: ['templateVersion:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $subnet = null;

    /**
     * Template version.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: TemplateVersion::class, inversedBy: 'masquerades')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TemplateVersion $templateVersion = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getSubnet();
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

    public function getSubnet(): ?string
    {
        return $this->subnet;
    }

    public function setSubnet(?string $subnet)
    {
        $this->subnet = $subnet;
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
