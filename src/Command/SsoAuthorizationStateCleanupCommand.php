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

use App\Entity\MicrosoftOidcAuthorizationState;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SsoAuthorizationStateCleanupCommand extends Command
{
    use EntityManagerTrait;

    protected function configure(): void
    {
        $this->setName('app:sso:authorization-state-cleanup');
        $this->setDescription('Cleanup expired SSO authorization states by removing them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = new \DateTime();

        $queryBuilder = $this->getRepository(MicrosoftOidcAuthorizationState::class)->createQueryBuilder('s');
        $queryBuilder->delete();
        $queryBuilder->andWhere('s.expireAt < :now');
        $queryBuilder->setParameter('now', $now);
        $count = $queryBuilder->getQuery()->execute();

        $io->comment('Removed '.$count.' expired Microsoft OIDC authorization states');

        return Command::SUCCESS;
    }
}
