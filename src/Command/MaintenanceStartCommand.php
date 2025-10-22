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

use App\Enum\MaintenanceType;
use App\Service\Helper\ActorProviderTrait;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\MaintenanceManagerTrait;
use App\Service\MaintenanceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceStartCommand extends Command
{
    use MaintenanceManagerTrait;
    use EncryptionManagerTrait;
    use ActorProviderTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance:start');
        $this->setDescription('Start next pending maintenance job when there are no in progress maintenance jobs');
        $this->setHelp(<<<EOT
        The <info>%command.name%</info> command starts next pending maintenance job when there are no in progress maintenance jobs.
        When pending job is started command outputs a set of information seperated by space.
        Information is structured as follows:

        For <info>"backup"</info> type: Maintenance ID, Maintenance type ("backup"), Include database ("0" or "1"), Include filestorage ("0" or "1"), Backup password (optional, encoded with base64)
        For <info>"restore"</info> type: Maintenance ID, Maintenance type ("restore"), Restore database ("0" or "1"), Restore filestorage ("0" or "1"), Restore filepath, Restore password (optional, encoded with base64)
        For <info>"backupForUpdate"</info> type: Maintenance ID, Maintenance type ("backupForUpdate")

        Example:
        12 backup 0 1 123456
        EOT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->actorProvider->setSystemActor();

        $maintenance = $this->maintenanceManager->start();

        if ($maintenance) {
            $parameters = [];
            $parameters[] = $maintenance->getId();
            $parameters[] = $maintenance->getType()->value;

            switch ($maintenance->getType()) {
                case MaintenanceType::BACKUP_FOR_UPDATE:
                    // No additional parameters in this case
                    break;
                case MaintenanceType::BACKUP:
                    $parameters[] = $maintenance->getBackupDatabase() ? '1' : '0';
                    $parameters[] = $maintenance->getBackupFilestorage() ? '1' : '0';

                    if ($maintenance->getBackupPassword()) {
                        $parameters[] = \base64_encode($this->encryptionManager->decrypt($maintenance->getBackupPassword()));
                    }
                    break;
                case MaintenanceType::RESTORE:
                    $parameters[] = $maintenance->getRestoreDatabase() ? '1' : '0';
                    $parameters[] = $maintenance->getRestoreFilestorage() ? '1' : '0';
                    $parameters[] = MaintenanceManager::BACKUP_DIRECTORY.'/'.$maintenance->getFilepath();

                    if ($maintenance->getRestorePassword()) {
                        $parameters[] = \base64_encode($this->encryptionManager->decrypt($maintenance->getRestorePassword()));
                    }
                    break;
            }

            $output->write(implode(' ', $parameters));
        }

        return Command::SUCCESS;
    }
}
