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

/**
 * Device's connection aggregation.
 */
#[ORM\Entity]
#[ORM\Index(name: 'idx_start_at', fields: ['startAt'])]
#[ORM\Index(name: 'idx_end_at', fields: ['endAt'])]
class DeviceConnectionAggregation
{
    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * How many times device connected in specified timeframe.
     */
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $connectionAmount = 0;

    /**
     * Date of timeframe start.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $startAt = null;

    /**
     * Date of timeframe end.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $endAt = null;

    #[ORM\ManyToOne(targetEntity: Device::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Device $device = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) ('Device connection aggregation');
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

    public function getConnectionAmount(): ?int
    {
        return $this->connectionAmount;
    }

    public function setConnectionAmount(?int $connectionAmount)
    {
        $this->connectionAmount = $connectionAmount;
    }

    public function getStartAt(): ?\DateTime
    {
        return $this->startAt;
    }

    public function setStartAt(?\DateTime $startAt)
    {
        $this->startAt = $startAt;
    }

    public function getEndAt(): ?\DateTime
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTime $endAt)
    {
        $this->endAt = $endAt;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }
}
