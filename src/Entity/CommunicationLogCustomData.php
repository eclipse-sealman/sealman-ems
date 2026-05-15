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
use App\Entity\Traits\CustomDataValuesInterface;
use App\Entity\Traits\CustomDataValuesTrait;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * CommunicationLogCustomData entity stores custom data values extracted from communication payloads for communication logs.
 *
 * Each record represents a custom data value associated with a communication log entry, including the value itself
 * and metadata about how it was extracted (name, type, variable name).
 */
#[ORM\Entity]
class CommunicationLogCustomData implements DenyInterface, CreatedAtEntityInterface, CustomDataValuesInterface
{
    use DenyTrait;
    use CreatedAtEntityTrait;
    use CustomDataValuesTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Groups(['communicationLogCustomData:public', 'communicationLog:identification'])]
    #[ORM\ManyToOne(targetEntity: CommunicationLog::class, inversedBy: 'communicationLogCustomData')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CommunicationLog $communicationLog = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getCommunicationLog(): ?CommunicationLog
    {
        return $this->communicationLog;
    }

    public function setCommunicationLog(?CommunicationLog $communicationLog)
    {
        $this->communicationLog = $communicationLog;
    }
}
