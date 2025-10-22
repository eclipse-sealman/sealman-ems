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

namespace App\Security\Hasher;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * This hasher always returns false when verifing password to prevent potential login attempts for SSO users
 * App\Entity\User getPasswordHasherName() selects this hasher for SSO users.
 */
class SsoHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return 'SSO';
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return false;
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
