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

use App\Service\Helper\ActorProviderTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\MaintenanceManagerTrait;
use App\Service\MaintenanceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class MaintenanceAutoRemoveBackupCommand extends Command
{
    use MaintenanceManagerTrait;
    use EntityManagerTrait;
    use ActorProviderTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance:auto-remove-backups');
        $this->setDescription('Automatically remove backups based on current configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->actorProvider->setSystemActor();

        $io = new SymfonyStyle($input, $output);

        $expiredBackups = $this->maintenanceManager->getExpiredBackups();
        $io->text('Found '.count($expiredBackups).' expired backups');

        $fs = new Filesystem();

        foreach ($expiredBackups as $expiredBackup) {
            $filepath = $expiredBackup->getFilepath();
            $fullFilepath = MaintenanceManager::BACKUP_DIRECTORY.'/'.$filepath;

            if (!$filepath) {
                $io->warning('Expired backup does not have archive file [ID = '.$expiredBackup->getId().']');
            } elseif (!$fs->exists($fullFilepath)) {
                $io->warning('Expired backup archive does not exist in '.$fullFilepath.' [ID = '.$expiredBackup->getId().']');
            } else {
                try {
                    $io->text('Removing expired backup archive located in '.$fullFilepath.' [ID = '.$expiredBackup->getId().']');

                    $fs->remove($fullFilepath);
                } catch (\Exception $e) {
                    $io->warning('Expired backup archive could not be removed [ID = '.$expiredBackup->getId().']. Error message: '.$e->getMessage());
                }
            }

            $io->text('Removing expired backup [ID = '.$expiredBackup->getId().']');

            $this->entityManager->remove($expiredBackup);
            $this->entityManager->flush();
        }

        return Command::SUCCESS;
    }
}
