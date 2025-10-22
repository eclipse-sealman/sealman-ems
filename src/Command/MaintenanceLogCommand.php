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

use App\Enum\LogLevel;
use App\Exception\MaintenanceException;
use App\Service\Helper\MaintenanceManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceLogCommand extends Command
{
    use MaintenanceManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance:log');
        $this->addArgument('maintenanceId', InputArgument::REQUIRED, 'In progress maintenance job ID to add log message');
        $this->addArgument('message', InputArgument::REQUIRED, 'Log message');
        $this->addArgument('logLevel', InputArgument::REQUIRED, 'Log level (debug, info, warning, error or critical)');
        $this->setDescription('Add log to maintenance job');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logLevel = match ($input->getArgument('logLevel')) {
            'debug' => LogLevel::DEBUG,
            'info' => LogLevel::INFO,
            'warning' => LogLevel::WARNING,
            'error' => LogLevel::ERROR,
            'critical' => LogLevel::CRITICAL,
            default => null,
        };

        if (null === $logLevel) {
            $output->writeln('Log level passed ('.$input->getArgument('logLevel').') is invalid');

            return Command::FAILURE;
        }

        $maintenanceId = (int) $input->getArgument('maintenanceId');
        $message = $input->getArgument('message');

        try {
            $this->maintenanceManager->log($maintenanceId, $message, $logLevel);
        } catch (MaintenanceException $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
