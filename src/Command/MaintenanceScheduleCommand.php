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
use App\Service\Helper\MaintenanceManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MaintenanceScheduleCommand extends Command
{
    use MaintenanceManagerTrait;
    use ActorProviderTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance:schedule');
        $this->setDescription('Create maintenance backup jobs based on maintenance schedules');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->actorProvider->setSystemActor();

        $io = new SymfonyStyle($input, $output);

        $pendingMaintenanceSchedules = $this->maintenanceManager->getPendingMaintenanceSchedules();
        $io->text('Found '.count($pendingMaintenanceSchedules).' pending maintenance schedule(s)');

        foreach ($pendingMaintenanceSchedules as $pendingMaintenanceSchedule) {
            $created = $this->maintenanceManager->createMaintenanceScheduleJob($pendingMaintenanceSchedule);
            if ($created) {
                $io->text('Created backup job based on maintenance schedule named "'.$pendingMaintenanceSchedule->getName().'" [ID = '.$pendingMaintenanceSchedule->getId().']');
            }

            $this->maintenanceManager->calculateMaintenanceScheduleNextJobAt($pendingMaintenanceSchedule);
            $io->text('Planned next backup job on '.$pendingMaintenanceSchedule->getNextJobAt()->format('d-m-Y H:i:s').' for maintenance schedule named "'.$pendingMaintenanceSchedule->getName().'" [ID = '.$pendingMaintenanceSchedule->getId().']');
        }

        return Command::SUCCESS;
    }
}
