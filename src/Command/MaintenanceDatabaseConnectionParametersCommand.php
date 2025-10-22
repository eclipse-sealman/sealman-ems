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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class MaintenanceDatabaseConnectionParametersCommand extends Command
{
    use EntityManagerTrait;

    /**
     * @var ContainerInterface
     */
    protected $container;

    #[Required]
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function configure(): void
    {
        $this->setName('app:maintenance:database-connection-parameters');
        $this->setDescription('Provides current database connection parameters for mysql and mysqldump commands');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $params = $this->entityManager->getConnection()->getParams();

        $connectionString = '-u '.($params['user'] ?? null);
        $connectionString .= ' -p'.$params['password'] ?? null;
        $connectionString .= ' -h '.$params['host'] ?? null;
        $connectionString .= ' -P '.$params['port'] ?? 3306;

        if ($this->container->hasParameter('mysqlServerValidation') && $this->container->getParameter('mysqlServerValidation')) {
            $connectionString .= ' --ssl-verify-server-cert';
        } else {
            $connectionString .= ' --skip-ssl-verify-server-cert';
        }

        if ($this->container->hasParameter('mysqlSslCa')) {
            $connectionString .= ' --ssl-ca='.$this->container->getParameter('mysqlSslCa');

            if ($this->container->hasParameter('mysqlSslCert') && $this->container->hasParameter('mysqlSslKey')) {
                $connectionString .= ' --ssl-cert='.$this->container->getParameter('mysqlSslCert');
                $connectionString .= ' --ssl-key='.$this->container->getParameter('mysqlSslKey');
            }
        }

        $output->write($connectionString);

        return Command::SUCCESS;
    }
}
