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

use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class UserTable
{
    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Table key.
     */
    #[Assert\NotBlank(groups: ['userTable:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $tableKey = null;

    /**
     * User.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Columns.
     */
    #[Groups(['userTable:public'])]
    #[Assert\Valid(groups: ['userTable:common'])]
    #[ORM\OneToMany(mappedBy: 'table', targetEntity: UserTableColumn::class)]
    #[ORM\OrderBy(['position' => 'asc'])]
    private Collection $columns;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getTableKey();
    }

    public function addColumn(UserTableColumn $column)
    {
        if (!$this->columns->contains($column)) {
            $this->columns[] = $column;
            $column->setTable($this);
        }
    }

    public function removeColumn(UserTableColumn $column)
    {
        if ($this->columns->removeElement($column)) {
            if ($column->getTable() === $this) {
                $column->setTable(null);
            }
        }
    }

    public function __construct()
    {
        $this->columns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getTableKey(): ?string
    {
        return $this->tableKey;
    }

    public function setTableKey(?string $tableKey)
    {
        $this->tableKey = $tableKey;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function getColumns(): Collection
    {
        return $this->columns;
    }

    public function setColumns(Collection $columns)
    {
        $this->columns = $columns;
    }
}
