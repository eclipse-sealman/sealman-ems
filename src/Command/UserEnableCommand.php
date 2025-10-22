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

use App\Entity\User;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserEnableCommand extends Command
{
    use EntityManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:user:enable');
        $this->addArgument('username', InputArgument::REQUIRED, 'The username of the user.');
        $this->setDescription('Mark user as enabled');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $user = $this->getRepository(User::class)->findOneBy([
            'username' => $username,
        ]);

        if (!$user) {
            $io->warning('Could not find user with username "'.$username.'".');

            return Command::SUCCESS;
        }

        if ($user->getIsEnabled()) {
            $io->info('User "'.$username.'" is already enabled.');

            return Command::SUCCESS;
        }

        if ($user->getRadiusUser()) {
            $io->warning('User with username "'.$username.'" is a radius user. Enabling this user is not possible.');

            return Command::SUCCESS;
        }

        if ($user->getSsoUser()) {
            $io->warning('User with username "'.$username.'" is a single sign-on (SSO) user. Enabling this user is not possible.');

            return Command::SUCCESS;
        }

        $user->setEnabled(true);
        $user->setEnabledExpireAt(null);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User "'.$username.'" has been enabled.');

        return Command::SUCCESS;
    }
}
