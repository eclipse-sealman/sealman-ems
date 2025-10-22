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
use App\Service\Helper\ConnectionAggregationManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeviceConnectionAggregationCommand extends Command
{
    use ConnectionAggregationManagerTrait;
    use ActorProviderTrait;

    protected function configure(): void
    {
        $this->setName('app:device:connection-aggregation');
        $this->setDescription('Updates device connection amount');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->actorProvider->setSystemActor();

        $this->connectionAggregationManager->updateAllDevicesConnectionAmount();

        return Command::SUCCESS;
    }
}
