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

namespace App\Command;

use App\Entity\Maintenance;
use App\Enum\MaintenanceStatus;
use App\Enum\MaintenanceType;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\MaintenanceManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MaintenanceCreateBackupForUpdateCommand extends Command
{
    use MaintenanceManagerTrait;
    use EntityManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance:create-backup-for-update');
        $this->setDescription('Create backup for update. IMPORANT! This command waits until backup for update has been finished.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maintenance = new Maintenance();
        $maintenance->setType(MaintenanceType::BACKUP_FOR_UPDATE);

        $this->entityManager->persist($maintenance);
        $this->entityManager->flush();

        $maintenanceId = $maintenance->getId();
        $this->entityManager->clear();

        // Timeout at 300 cycles (~5 minutes)
        $timeout = 300;
        $cycles = 1;

        do {
            $backupForUpdate = $this->getRepository(Maintenance::class)->find($maintenanceId);
            if (!$backupForUpdate) {
                $io->error('Could not find maintenance job for created backup for update [ID = '.$maintenanceId.'].');

                return Command::FAILURE;
            }

            if (MaintenanceStatus::FAILED === $backupForUpdate->getStatus()) {
                $io->error('Maintenance job for created backup for update [ID = '.$maintenanceId.'] has failed.');

                return Command::FAILURE;
            }

            if (MaintenanceStatus::SUCCESS === $backupForUpdate->getStatus()) {
                $io->success('Maintenance job for created backup for update [ID = '.$maintenanceId.'] has finished successfully.');

                return Command::SUCCESS;
            }

            if (MaintenanceStatus::PENDING === $backupForUpdate->getStatus() || MaintenanceStatus::IN_PROGRESS === $backupForUpdate->getStatus()) {
                $io->writeln('Maintenance job for created backup for update [ID = '.$maintenanceId.'] is still pending or in progress. Retrying ('.$cycles.'/'.$timeout.')');
                sleep(1);
            }

            $this->entityManager->clear();

            ++$cycles;
        } while ($cycles <= $timeout);

        $io->error('Timeout reached. Maintenance job for created backup for update [ID = '.$maintenanceId.'] has not finished in time.');

        return Command::FAILURE;
    }
}
