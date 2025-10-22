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

namespace App\Model;

use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class VpnContainerClientModel
{
    /**
     * VCC name.
     */
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    /**
     *  VCC UUID.
     */
    #[Assert\Length(max: 255, groups: ['Default', 'authentication'])]
    private ?string $uuid = null;

    /**
     * Provided logs.
     */
    #[Assert\Valid(groups: ['vpnContainerClientLogs'])]
    private Collection|array $logs;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getLogs(): Collection|array
    {
        return $this->logs;
    }

    public function setLogs(Collection|array $logs)
    {
        $this->logs = $logs;
    }
}
