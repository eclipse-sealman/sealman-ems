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

use App\Entity\Traits\LogLevelEntityTrait;
use App\Entity\Traits\LogLevelInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class ImportFileRowLog implements TimestampableEntityInterface, LogLevelInterface
{
    use TimestampableEntityTrait;
    use LogLevelEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Excel column key (starting from 0).
     */
    #[Groups(['importFileRowLog:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $columnName = null;

    /**
     * Message.
     */
    #[Groups(['importFileRowLog:public'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    /**
     * Import file.
     */
    #[ORM\ManyToOne(targetEntity: ImportFileRow::class, inversedBy: 'logs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ImportFileRow $row = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getMessage();
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message)
    {
        $this->message = $message;
    }

    public function getRow(): ?ImportFileRow
    {
        return $this->row;
    }

    public function setRow(?ImportFileRow $row)
    {
        $this->row = $row;
    }

    public function getColumnName(): ?string
    {
        return $this->columnName;
    }

    public function setColumnName(?string $columnName)
    {
        $this->columnName = $columnName;
    }
}
