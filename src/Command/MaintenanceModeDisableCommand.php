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

use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MaintenanceModeDisableCommand extends Command
{
    use ConfigurationManagerTrait;
    use EntityManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance-mode:disable');
        $this->setDescription('Disable maintenance mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $configuration = $this->getConfiguration();
        if (!$configuration->getMaintenanceMode()) {
            $io->text('Maintenance mode is already disabled');

            return Command::SUCCESS;
        }

        $configuration->setMaintenanceMode(false);

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        $io->success('Maintenance mode has been disabled');

        return Command::SUCCESS;
    }
}
