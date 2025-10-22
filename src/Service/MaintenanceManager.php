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

use App\Entity\Maintenance;
use App\Entity\MaintenanceLog;
use App\Entity\MaintenanceSchedule;
use App\Enum\LogLevel;
use App\Enum\MaintenanceStatus;
use App\Enum\MaintenanceType;
use App\Exception\MaintenanceException;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Cron\CronExpression;
use Symfony\Component\Finder\Finder;

class MaintenanceManager
{
    use EntityManagerTrait;
    use ConfigurationManagerTrait;

    public const BACKUP_DIRECTORY = '/var/www/application/archive/backup';

    public function getExpiredBackups()
    {
        $autoRemoveBackupsAfter = $this->getConfiguration()->getAutoRemoveBackupsAfter();
        if (0 === $autoRemoveBackupsAfter) {
            return [];
        }

        $expireAt = new \DateTime();
        $expireAt->modify('-'.$autoRemoveBackupsAfter.' days');

        $queryBuilder = $this->getRepository(Maintenance::class)->createQueryBuilder('m');
        $queryBuilder->andWhere('m.updatedAt <= :updatedAt');
        $queryBuilder->setParameter('updatedAt', $expireAt);
        $queryBuilder->andWhere('m.type <= :type');
        $queryBuilder->setParameter('type', MaintenanceType::BACKUP);
        $queryBuilder->andWhere('m.status <= :status');
        $queryBuilder->setParameter('status', MaintenanceStatus::SUCCESS);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getPendingMaintenanceSchedules()
    {
        $queryBuilder = $this->getRepository(MaintenanceSchedule::class)->createQueryBuilder('m');
        $queryBuilder->andWhere('m.nextJobAt <= :nextJobAt OR m.nextJobAt IS NULL');
        $queryBuilder->setParameter('nextJobAt', new \DateTime());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Create backup job based on maintenance schedule. Return true when backup job has been created and false otherwise.
     */
    public function createMaintenanceScheduleJob(MaintenanceSchedule $maintenanceSchedule): bool
    {
        if (!$maintenanceSchedule->getNextJobAt()) {
            return false;
        }

        if ($maintenanceSchedule->getNextJobAt() > new \DateTime()) {
            return false;
        }

        $maintenance = new Maintenance();
        $maintenance->setType(MaintenanceType::BACKUP);
        $maintenance->setScheduledBackup(true);
        $maintenance->setBackupDatabase($maintenanceSchedule->getBackupDatabase());
        $maintenance->setBackupPassword($maintenanceSchedule->getBackupPassword());
        $maintenance->setBackupFilestorage($maintenanceSchedule->getBackupFilestorage());
        $maintenance->setMaintenanceSchedule($maintenanceSchedule);

        $this->entityManager->persist($maintenance);
        $this->entityManager->flush();

        return true;
    }

    public function calculateMaintenanceScheduleNextJobAt(MaintenanceSchedule $maintenanceSchedule): void
    {
        $maintenanceSchedule->setNextJobAt($this->getNextJobAt($maintenanceSchedule));

        $this->entityManager->persist($maintenanceSchedule);
        $this->entityManager->flush();
    }

    public function getNextJobAt(MaintenanceSchedule $maintenanceSchedule): \DateTime
    {
        $minute = (-1 == $maintenanceSchedule->getMinute() ? '*' : $maintenanceSchedule->getMinute());
        $hour = (-1 == $maintenanceSchedule->getHour() ? '*' : $maintenanceSchedule->getHour());
        $dow = (-1 == $maintenanceSchedule->getDayOfWeek() ? '*' : $maintenanceSchedule->getDayOfWeek());
        $dom = (-1 == $maintenanceSchedule->getDayOfMonth() ? '*' : $maintenanceSchedule->getDayOfMonth());

        $cronString = $minute;
        $cronString .= ' '.$hour;
        $cronString .= ' '.$dom;
        $cronString .= ' *'; // Month
        $cronString .= ' '.$dow;

        $cron = CronExpression::factory($cronString);

        return $cron->getNextRunDate();
    }

    public function findInProgress(): ?Maintenance
    {
        return $this->getRepository(Maintenance::class)->findOneBy(['status' => MaintenanceStatus::IN_PROGRESS]);
    }

    public function findPending(): ?Maintenance
    {
        return $this->getRepository(Maintenance::class)->findOneBy(['status' => MaintenanceStatus::PENDING], ['createdAt' => 'ASC']);
    }

    public function start(): ?Maintenance
    {
        $maintenanceInProgress = $this->findInProgress();

        if ($maintenanceInProgress) {
            return null;
        }

        $maintenancePending = $this->findPending();
        if (!$maintenancePending) {
            return null;
        }

        $maintenancePending->setStatus(MaintenanceStatus::IN_PROGRESS);

        $this->entityManager->persist($maintenancePending);
        $this->entityManager->flush();

        return $maintenancePending;
    }

    public function log(int $maintenanceId, string $message, LogLevel $logLevel): void
    {
        $maintenance = $this->findInProgress();
        if (!$maintenance) {
            throw new MaintenanceException('There is no maintenance job in progress that you can add log to');
        }

        if ($maintenance->getId() !== $maintenanceId) {
            throw new MaintenanceException('Maintenance job ID ('.$maintenanceId.') does not match the one that is in progress ('.$maintenance->getId().')');
        }

        $maintenanceLog = new MaintenanceLog();
        $maintenanceLog->setMaintenance($maintenance);
        $maintenanceLog->setMessage($message);
        $maintenanceLog->setLogLevel($logLevel);

        $this->entityManager->persist($maintenanceLog);
        $this->entityManager->flush();
    }

    public function finishSuccess(int $maintenanceId, ?string $backupFilepath = null): void
    {
        $maintenance = $this->findInProgress();
        if (!$maintenance) {
            throw new MaintenanceException('There is no maintenance job in progress that can be marked as finished with success status');
        }

        if ($maintenance->getId() !== $maintenanceId) {
            throw new MaintenanceException('Maintenance job ID ('.$maintenanceId.') does not match the one that is in progress ('.$maintenance->getId().')');
        }

        $isBackup = MaintenanceType::BACKUP === $maintenance->getType() || MaintenanceType::BACKUP_FOR_UPDATE === $maintenance->getType();
        if (!$backupFilepath && $isBackup) {
            throw new MaintenanceException('Maintenance job with ID '.$maintenance->getId().' has type "'.$maintenance->getType()->value.'" which requires backupFilepath parameter to be marked as finished with success status');
        }

        $maintenance->setStatus(MaintenanceStatus::SUCCESS);
        $maintenance->setFilepath($backupFilepath);

        $this->entityManager->persist($maintenance);
        $this->entityManager->flush();
    }

    public function finishFailed(int $maintenanceId): void
    {
        $maintenance = $this->findInProgress();
        if (!$maintenance) {
            throw new MaintenanceException('There is no maintenance job in progress that can be marked as finished with failed status');
        }

        if ($maintenance->getId() !== $maintenanceId) {
            throw new MaintenanceException('Maintenance job ID ('.$maintenanceId.') does not match the one that is in progress ('.$maintenance->getId().')');
        }

        $maintenance->setStatus(MaintenanceStatus::FAILED);

        $this->entityManager->persist($maintenance);
        $this->entityManager->flush();
    }

    public function getRestoreArchiveFilepaths(): array
    {
        $filepaths = [];

        $finder = new Finder();
        $finder->files()->in(self::BACKUP_DIRECTORY);
        $finder->sortByName(true)->reverseSorting();

        foreach ($finder as $file) {
            $filepaths[] = [
                'id' => $file->getFilename(),
                'representation' => $file->getFilename(),
            ];
        }

        return $filepaths;
    }
}
