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

class MaintenanceDatabaseVerifyEmptyCommand extends Command
{
    use EntityManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:maintenance:database-verify-empty');
        $this->setDescription('Verify whether database is empty (does not have tables or views). Command exits with 0 when database is empty, otherwise exits with 1.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->entityManager->getConnection();

        $sql = 'SELECT COUNT(DISTINCT `table_name`) as table_count FROM `information_schema`.`columns` WHERE `table_schema` = :databaseName';
        $statement = $connection->prepare($sql);
        $statement->bindValue('databaseName', $connection->getDatabase());
        $result = $statement->executeQuery();

        $tableCount = $result->fetchOne();

        if ($tableCount > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
