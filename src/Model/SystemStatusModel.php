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
 * System status information.
 */
class SystemStatusModel
{
    /**
     * Ram usage.
     */
    #[Groups(['status:public'])]
    private ?string $ram = null;

    /**
     * Cpu usage.
     */
    #[Groups(['status:public'])]
    private ?string $cpu = null;

    /**
     * Filesystem usage.
     */
    #[Groups(['status:public'])]
    private ?string $filesystem = null;

    /**
     * Disk space left in percent.
     */
    #[Groups(['status:public'])]
    private int|float|null $diskPercentLeft = null;

    /**
     * Database size.
     */
    #[Groups(['status:public'])]
    private ?string $databaseSize = null;

    /**
     * OperatingSystem.
     */
    #[Groups(['status:public'])]
    private ?string $operatingSystem = null;

    /**
     * System time.
     */
    #[Groups(['status:public'])]
    private ?string $systemTime = null;

    /**
     * Application version.
     */
    #[Groups(['status:public'])]
    private ?string $appVersion = null;

    public function getRam(): ?string
    {
        return $this->ram;
    }

    public function setRam(?string $ram)
    {
        $this->ram = $ram;
    }

    public function getCpu(): ?string
    {
        return $this->cpu;
    }

    public function setCpu(?string $cpu)
    {
        $this->cpu = $cpu;
    }

    public function getFilesystem(): ?string
    {
        return $this->filesystem;
    }

    public function setFilesystem(?string $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getDiskPercentLeft(): int|float|null
    {
        return $this->diskPercentLeft;
    }

    public function setDiskPercentLeft(int|float|null $diskPercentLeft)
    {
        $this->diskPercentLeft = $diskPercentLeft;
    }

    public function getDatabaseSize(): ?string
    {
        return $this->databaseSize;
    }

    public function setDatabaseSize(?string $databaseSize)
    {
        $this->databaseSize = $databaseSize;
    }

    public function getOperatingSystem(): ?string
    {
        return $this->operatingSystem;
    }

    public function setOperatingSystem(?string $operatingSystem)
    {
        $this->operatingSystem = $operatingSystem;
    }

    public function getSystemTime(): ?string
    {
        return $this->systemTime;
    }

    public function setSystemTime(?string $systemTime)
    {
        $this->systemTime = $systemTime;
    }

    public function getAppVersion(): ?string
    {
        return $this->appVersion;
    }

    public function setAppVersion(?string $appVersion)
    {
        $this->appVersion = $appVersion;
    }
}
