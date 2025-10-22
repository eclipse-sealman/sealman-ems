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

use App\Enum\MaintenanceStatus;
use App\Exception\MaintenanceException;
use App\Service\Helper\ActorProviderTrait;
use App\Service\Helper\MaintenanceManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceFinishCommand extends Command
{
    use MaintenanceManagerTrait;
    use ActorProviderTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance:finish');
        $this->addArgument('maintenanceId', InputArgument::REQUIRED, 'In progress maintenance job ID that should be marked as finished');
        $this->addArgument('status', InputArgument::REQUIRED, 'Maintenance job finished status (success or failed)');
        $this->addArgument('backupFilepath', InputArgument::OPTIONAL, 'Backup filepath. Parameter required when successfully finishing backup or backupForUpdate maintenance job.');
        $this->setDescription('Finish in progress maintenance job');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->actorProvider->setSystemActor();

        $status = match ($input->getArgument('status')) {
            'success' => MaintenanceStatus::SUCCESS,
            'failed' => MaintenanceStatus::FAILED,
            default => null,
        };

        if (null === $status) {
            $output->writeln('Status passed ('.$input->getArgument('status').') is invalid');

            return Command::FAILURE;
        }

        $maintenanceId = (int) $input->getArgument('maintenanceId');
        $backupFilepath = $input->getArgument('backupFilepath');

        try {
            if (MaintenanceStatus::SUCCESS === $status) {
                $this->maintenanceManager->finishSuccess($maintenanceId, $backupFilepath);
            }

            if (MaintenanceStatus::FAILED === $status) {
                $this->maintenanceManager->finishFailed($maintenanceId);
            }
        } catch (MaintenanceException $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
