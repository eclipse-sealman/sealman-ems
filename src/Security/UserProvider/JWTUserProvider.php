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

namespace App\Security\UserProvider;

use App\Entity\User;
use App\Service\Helper\EntityManagerTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\PayloadAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTUserProvider implements PayloadAwareUserProviderInterface
{
    use EntityManagerTrait;

    /**
     * @deprecated since 2.12, implement loadUserByIdentifierAndPayload() instead.
     */
    public function loadUserByUsernameAndPayload(string $username, array $payload = []): UserInterface
    {
        return $this->loadUserByIdentifierAndPayload($username, $payload);
    }

    public function loadUserByIdentifierAndPayload(string $identifier, array $payload = []): UserInterface
    {
        $user = $this->loadUserByIdentifier($identifier);

        if ($user->getSsoUser()) {
            $iat = $payload['iat'] ?? null;
            $ssoLogoutAt = $user->getSsoLogoutAt();
            if ($iat && $ssoLogoutAt && $ssoLogoutAt->getTimestamp() > $iat) {
                throw new UserNotFoundException();
            }
        }

        return $user;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->getRepository(User::class)->findOneBy(['username' => $identifier]);

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    /**
     * @deprecated since Symfony 5.3, use loadUserByIdentifier() instead
     */
    public function loadUserByUsername(string $username)
    {
        return $this->loadUserByIdentifier($username);
    }

    public function supportsClass($class): bool
    {
        return User::class === $class;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user; // noop
    }
}
