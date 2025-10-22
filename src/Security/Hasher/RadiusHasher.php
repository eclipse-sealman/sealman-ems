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

use Symfony\Component\PasswordHasher\LegacyPasswordHasherInterface;

class RadiusHasher implements LegacyPasswordHasherInterface
{
    /**
     * {@inheritdoc}
     */
    public function needsRehash(string $hashedPassword): bool
    {
        // Radius doesn't handle passwords in database
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hash(string $plainPassword, null|string $salt = null): string
    {
        // Radius doesn't handle passwords in database
        // This value will not work with any other hashing algorithms
        return 'RADIUS';
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string $hashedPassword, string $plainPassword, null|string $salt = null): bool
    {
        // If Radius user password will be verified by standard credentials check it will fail
        // only RadiusCheckCredentialsSubscriber will check radius credentials properly
        return false;
    }
}
