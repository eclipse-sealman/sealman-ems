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
 * This hasher always returns false when verifing password to prevent potential login attempts for device secret and device x509 users
 * App\Entity\User getPasswordHasherName() selects this hasher for device secret and device x509 users.
 */
class NoPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return 'NoPassword';
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
