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

use App\Exception\LogsException;
use App\Service\Helper\ActorProviderTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\VpnManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CloseExpiredVpnConnectionCommand extends Command
{
    use VpnManagerTrait;
    use TranslatorTrait;
    use ActorProviderTrait;

    protected function configure(): void
    {
        $this->setName('app:close-expired-vpn-connection');
        $this->setDescription('Close expired VPN connections');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->actorProvider->setSystemActor();

        $io = new SymfonyStyle($input, $output);

        $expiredConnections = $this->vpnManager->getExpiredConnections();

        foreach ($expiredConnections as $expiredConnection) {
            $userName = 'unknown';
            $targetName = 'unknown';
            $connectionEndAt = 'unknown';

            if ($expiredConnection->getUser() && $expiredConnection->getUser()->getUsername()) {
                $userName = $expiredConnection->getUser()->getUsername();
            }
            if ($expiredConnection->getTarget() && $expiredConnection->getTarget()->getName()) {
                $targetName = $expiredConnection->getTarget()->getName();
            }
            if ($expiredConnection->getConnectionEndAt()) {
                $connectionEndAt = $expiredConnection->getConnectionEndAt()->format('d-m-Y H:i:s');
            }

            $io->comment('Closing expired connection for '.$userName.' connected to '.$targetName.'. Connection expired at '.$connectionEndAt);
            try {
                $this->vpnManager->closeConnection($expiredConnection);
            } catch (LogsException $e) {
                $errors = '';
                foreach ($e->getErrors() as $error) {
                    $errors .= "\n".$this->trans($error['message'], $error['parameters']);
                }

                $io->error('Closing connection failed: '.$errors);
            }
        }

        if (0 === count($expiredConnections)) {
            $io->comment('No expired connections found');
        }

        return Command::SUCCESS;
    }
}
