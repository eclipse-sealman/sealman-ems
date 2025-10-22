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

namespace App\Command;

use Carve\ApiBundle\Service\Helper\EntityManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MaintenanceDatabaseNameCommand extends Command
{
    use EntityManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance:database-name');
        $this->setDescription('Provides current database name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $params = $this->entityManager->getConnection()->getParams();

        $output->write($params['dbname'] ?? null);

        return Command::SUCCESS;
    }
}
