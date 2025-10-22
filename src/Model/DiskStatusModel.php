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

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Disk status information.
 */
class DiskStatusModel
{
    /**
     * Is alarm enabled.
     */
    #[Groups(['status:public'])]
    private ?bool $alert = null;

    /**
     * Disk space bytes used.
     */
    #[Groups(['status:public'])]
    private int|float|null $usage = null;

    /**
     * Disk space total bytes.
     */
    #[Groups(['status:public'])]
    private int|float|null $total = null;

    public function getAlert(): ?bool
    {
        return $this->alert;
    }

    public function setAlert(?bool $alert)
    {
        $this->alert = $alert;
    }

    public function getUsage(): int|float|null
    {
        return $this->usage;
    }

    public function setUsage(int|float|null $usage)
    {
        $this->usage = $usage;
    }

    public function getTotal(): int|float|null
    {
        return $this->total;
    }

    public function setTotal(int|float|null $total)
    {
        $this->total = $total;
    }
}
