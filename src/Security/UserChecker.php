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

namespace App\Security;

use App\Entity\User;
use App\Service\Helper\AuthenticationManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    use EntityManagerTrait;
    use AuthenticationManagerTrait;
    use ConfigurationManagerTrait;

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->getIsEnabled()) {
            throw new CustomUserMessageAccountStatusException('accountDisabled');
        }

        if ($this->authenticationManager->hasUserTooManyFailedLoginAttemptsSet($user)) {
            if ($this->authenticationManager->isUserDuringTooManyFailedLoginAttemptsDuration($user)) {
                throw new CustomUserMessageAccountStatusException('accountDisabledTooManyFailedLoginAttempts');
            }

            // Note: resetLoginAttempts function does not execute entityManager->flush()
            $this->authenticationManager->resetLoginAttempts($user);
            $this->entityManager->flush();
        }

        if ($user->getRoleVpn() && !$user->getRoleSmartems() && !$user->getRoleAdmin() && $this->configurationManager->isVpnSecuritySuiteBlocked()) {
            throw new CustomUserMessageAccountStatusException('accessDeniedNoVpnSecuritySuite');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Empty function
        return;
    }
}
