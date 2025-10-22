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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class AuditLogChangeValues
{
    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Entity old values. JSON value as string. Encrypted values are represented by two UUIDs.
     * "d460d32e-0028-11ef-92c8-0242ac120002" means that encrypted value has not changed.
     * "33b8afee-6b74-4742-a2ae-a47fdfb1ab57" means that encrypted value has changed.
     */
    #[Groups(['auditLogChangeValues:public'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $oldValues = null;

    /**
     * Entity new values. JSON value as string. Encrypted values are represented by two UUIDs.
     * "d460d32e-0028-11ef-92c8-0242ac120002" means that encrypted value has not changed.
     * "33b8afee-6b74-4742-a2ae-a47fdfb1ab57" means that encrypted value has changed.
     */
    #[Groups(['auditLogChangeValues:public'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $newValues = null;

    #[ORM\OneToOne(targetEntity: AuditLogChange::class, inversedBy: 'auditLogChangeValues')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AuditLogChange $auditLogChange = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) ('AuditLogChangeValues');
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

    public function getOldValues(): ?string
    {
        return $this->oldValues;
    }

    public function setOldValues(?string $oldValues)
    {
        $this->oldValues = $oldValues;
    }

    public function getNewValues(): ?string
    {
        return $this->newValues;
    }

    public function setNewValues(?string $newValues)
    {
        $this->newValues = $newValues;
    }

    public function getAuditLogChange(): ?AuditLogChange
    {
        return $this->auditLogChange;
    }

    public function setAuditLogChange(?AuditLogChange $auditLogChange)
    {
        $this->auditLogChange = $auditLogChange;
    }
}
