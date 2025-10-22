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

use App\Entity\DeviceCommand;
use App\Enum\DeviceCommandStatus;
use App\Service\Helper\ActorProviderTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeviceCommandExpireCommand extends Command
{
    use EntityManagerTrait;
    use ActorProviderTrait;

    protected function configure(): void
    {
        $this->setName('app:device-command:expire');
        $this->setDescription('Mark device commands as expired');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->actorProvider->setSystemActor();

        $io = new SymfonyStyle($input, $output);

        $expiredCommands = $this->getExpiredCommands();
        $io->comment('Number of expired device commands found = '.count($expiredCommands));

        foreach ($expiredCommands as $expiredCommand) {
            $io->comment('Marking device command as expired [ID = '.$expiredCommand->getId().']');

            $this->expireCommand($expiredCommand);
        }

        return Command::SUCCESS;
    }

    public function getExpiredCommands()
    {
        $queryBuilder = $this->getRepository(DeviceCommand::class)->createQueryBuilder('dc');
        $queryBuilder->andWhere('dc.expireAt < :now');
        $queryBuilder->andWhere('dc.commandStatus = :commandStatus');
        $queryBuilder->setParameter('now', new \DateTime());
        $queryBuilder->setParameter('commandStatus', DeviceCommandStatus::PENDING);

        return $queryBuilder->getQuery()->getResult();
    }

    public function expireCommand(DeviceCommand $command): void
    {
        $command->setCommandStatus(DeviceCommandStatus::EXPIRED);

        $this->entityManager->persist($command);
        $this->entityManager->flush();
    }
}
