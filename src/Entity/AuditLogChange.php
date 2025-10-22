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

use App\Entity\Traits\CreatedAtEntityInterface;
use App\Entity\Traits\CreatedAtEntityTrait;
use App\Enum\AuditLogChangeType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Index(name: 'idx_createdAt_id', columns: ['created_at', 'id'])]
#[ORM\Index(name: 'idx_type', columns: ['type'])]
class AuditLogChange implements CreatedAtEntityInterface
{
    use CreatedAtEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Type.
     */
    #[Groups(['auditLogChange:public'])]
    #[ORM\Column(type: Types::STRING, enumType: AuditLogChangeType::class)]
    private ?AuditLogChangeType $type = null;

    /**
     * Does old and new values contain only changed values?
     */
    #[Groups(['auditLogChange:public'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $onlyChanges = false;

    /**
     * Entity short name.
     */
    #[Groups(['auditLogChange:public'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $entityName = null;

    /**
     * Entity ID.
     */
    #[Groups(['auditLogChange:public'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $entityId = null;

    #[Groups(['createdBy', 'blameable'])]
    #[Gedmo\Blameable(on: 'create')]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[Groups(['auditLogChange:public'])]
    #[ORM\ManyToOne(targetEntity: AuditLog::class, inversedBy: 'changes')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?AuditLog $log;

    #[Groups(['auditLogChangeValues:public'])]
    #[ORM\OneToOne(targetEntity: AuditLogChangeValues::class, mappedBy: 'auditLogChange')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?AuditLogChangeValues $auditLogChangeValues = null;

    /**
     * Helper fields for AuditableListener.
     * Entity old values. JSON value as string. Encrypted values are represented by two UUIDs.
     * "d460d32e-0028-11ef-92c8-0242ac120002" means that encrypted value has not changed.
     * "33b8afee-6b74-4742-a2ae-a47fdfb1ab57" means that encrypted value has changed.
     */
    #[Groups(['auditLogChange:public'])]
    private ?string $oldValues = null;

    /**
     * Helper fields for AuditableListener.
     * Entity new values. JSON value as string. Encrypted values are represented by two UUIDs.
     * "d460d32e-0028-11ef-92c8-0242ac120002" means that encrypted value has not changed.
     * "33b8afee-6b74-4742-a2ae-a47fdfb1ab57" means that encrypted value has changed.
     */
    #[Groups(['auditLogChange:public'])]
    private ?string $newValues = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getEntityName();
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

    public function getType(): ?AuditLogChangeType
    {
        return $this->type;
    }

    public function setType(?AuditLogChangeType $type)
    {
        $this->type = $type;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function setEntityName(?string $entityName)
    {
        $this->entityName = $entityName;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId)
    {
        $this->entityId = $entityId;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy)
    {
        $this->createdBy = $createdBy;
    }

    public function getLog(): ?AuditLog
    {
        return $this->log;
    }

    public function setLog(?AuditLog $log)
    {
        $this->log = $log;
    }

    public function getOnlyChanges(): ?bool
    {
        return $this->onlyChanges;
    }

    public function setOnlyChanges(?bool $onlyChanges)
    {
        $this->onlyChanges = $onlyChanges;
    }

    public function getAuditLogChangeValues(): ?AuditLogChangeValues
    {
        return $this->auditLogChangeValues;
    }

    public function setAuditLogChangeValues(?AuditLogChangeValues $auditLogChangeValues)
    {
        $this->auditLogChangeValues = $auditLogChangeValues;
    }
}
