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
class CommunicationLogContent
{
    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\OneToOne(targetEntity: CommunicationLog::class, inversedBy: 'communicationLogContent')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CommunicationLog $communicationLog = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) ('CommunicationLogContent');
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content)
    {
        $this->content = $content;
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
