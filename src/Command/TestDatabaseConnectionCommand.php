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

use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestDatabaseConnectionCommand extends Command
{
    use EntityManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:test:database-connection');
        $this->setDescription('Test if connection to database can be established. When connection can be established command exits with status 0, otherwise exits with status 1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = Command::FAILURE;

        try {
            $this->entityManager->getConnection()->connect();
            $connected = $this->entityManager->getConnection()->isConnected();
            if ($connected) {
                $result = Command::SUCCESS;
            }
        } catch (\Exception $e) {
            // Do nothing
        }

        // Doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;

        return $result;
    }
}
