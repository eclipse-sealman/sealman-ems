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

namespace App\Service;

use App\Model\DiskStatusModel;
use App\Model\SystemStatusModel;
use App\Service\Helper\AppVersionTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Linfo\Linfo;

class SystemStatusManager
{
    use AppVersionTrait;
    use ConfigurationManagerTrait;
    use EntityManagerTrait;

    /**
     * @var Linfo
     */
    protected $linfo;

    public function getLinfo(): Linfo
    {
        if (!$this->linfo) {
            $this->linfo = new Linfo();
            $this->linfo->getParser()->determineCPUPercentage();
        }

        return $this->linfo;
    }

    public function getSystemStatus(): SystemStatusModel
    {
        $systemStatus = new SystemStatusModel();

        $systemStatus->setRam($this->getRam());
        $systemStatus->setCpu($this->getCpu());
        $systemStatus->setFilesystem($this->getFilesystem());
        $systemStatus->setDiskPercentLeft($this->getDiskPercentLeft());
        $systemStatus->setDatabaseSize($this->getDatabaseSize());
        $systemStatus->setOperatingSystem($this->getOperatingSystem());
        $systemStatus->setSystemTime($this->getSystemTime());
        $systemStatus->setAppVersion($this->appVersion);

        return $systemStatus;
    }

    public function getDiskStatus(): DiskStatusModel
    {
        $diskStatus = new DiskStatusModel();

        $diskStatus->setAlert($this->getDiskPercentLeft() < $this->getDiskUsageAlarmPercent());
        $diskStatus->setUsage($this->getDiskUsed());
        $diskStatus->setTotal($this->getDiskSize());

        return $diskStatus;
    }

    public function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    public function getRam(): string
    {
        $ramUsage = $this->getLinfo()->getParser()->getRam();
        $ramUsageTotal = $ramUsage['total'];
        $ramUsageUsed = $ramUsageTotal - $ramUsage['free'];

        $ram = round($ramUsageUsed / $ramUsageTotal, 2) * 100 .'%';
        $ram .= ' ('.$this->formatBytes($ramUsageUsed).' / '.$this->formatBytes($ramUsageTotal).')';

        return $ram;
    }

    public function getOperatingSystem(): string
    {
        $os = $this->getLinfo()->getParser()->getOS();
        $distro = $this->getLinfo()->getParser()->getDistro();
        $os .= ', '.$distro['name'].' '.$distro['version'];

        return $os;
    }

    public function getFilesystem(): string
    {
        $total = disk_total_space('/');
        $used = $total - disk_free_space('/');

        $filesystem = round($used / $total, 2) * 100 .'%';
        $filesystem .= ' ('.$this->formatBytes($used).' / '.$this->formatBytes($total).')';

        return $filesystem;
    }

    public function getCpu(): string
    {
        return round($this->getLinfo()->getCpuUsage()).'%';
    }

    public function getSystemTime(): string
    {
        $now = new \DateTime();

        return $now->format('H:i:s d-m-Y');
    }

    public function getDatabaseSize(): string
    {
        $sql = 'SELECT SUM(data_length + index_length) / 1024 / 1024 AS size
                FROM information_schema.TABLES 
                WHERE table_schema = "'.$this->entityManager->getConnection()->getDatabase().'"
                GROUP BY table_schema';

        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($sql);
        $resultSet = $stmt->executeQuery();

        $sizeResult = $resultSet->fetchAllAssociative();

        if (isset($sizeResult[0]) && isset($sizeResult[0]['size'])) {
            $size = number_format((float) $sizeResult[0]['size'], 2, '.', ' ').' MB';
        } else {
            $size = 'Unknown';
        }

        return $size;
    }

    public function getDiskPercentLeft(): int|float
    {
        $diskSize = $this->getDiskSize();
        $diskUsed = $this->getDiskUsed();

        return 0 != $diskSize ? (100 - round($diskUsed * 100 / $diskSize)) : 0;
    }

    public function getDiskUsed(): int|float
    {
        return $this->getDiskSize() - disk_free_space('/');
    }

    public function getDiskSize(): int|float
    {
        return disk_total_space('/');
    }

    public function getDiskUsageAlarmPercent(): string
    {
        return str_replace('%', '', (string) $this->getConfiguration()->getDiskUsageAlarm());
    }

    public function isDiskUsageExceeded(): bool
    {
        return $this->getDiskPercentLeft() < $this->getDiskUsageAlarmPercent();
    }
}
