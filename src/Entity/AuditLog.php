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

use App\Entity\Traits\CreatedAtByEntityInterface;
use App\Entity\Traits\CreatedAtByEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Index(name: 'idx_createdAt', columns: ['created_at'])]
class AuditLog implements CreatedAtByEntityInterface
{
    use CreatedAtByEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Changes.
     */
    #[ORM\OneToMany(mappedBy: 'log', targetEntity: AuditLogChange::class)]
    private Collection $changes;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getId();
    }

    public function __construct()
    {
        $this->changes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getChanges(): Collection
    {
        return $this->changes;
    }

    public function setChanges(Collection $changes)
    {
        $this->changes = $changes;
    }
}
