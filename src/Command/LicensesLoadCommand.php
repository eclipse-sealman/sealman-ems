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

use App\Service\Helper\OpenSourceLicenseManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LicensesLoadCommand extends Command
{
    use OpenSourceLicenseManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:licenses:load');
        $this->setDescription('Load licenses from licenses/licenses.csv into database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->openSourceLicenseManager->isLoadRequired()) {
            $io->comment('Loading licenses is not required');

            return Command::SUCCESS;
        }

        $io->comment('Loading licenses from licenses/licenses.csv into database');
        $this->openSourceLicenseManager->load();

        return Command::SUCCESS;
    }
}
