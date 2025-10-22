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

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDeviceSecretCredentialsFixtures extends Fixture implements FixtureGroupInterface
{
    /**
     * @var UserPasswordHasherInterface
     */
    protected $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $deviceSecretCredential = new User();
        $deviceSecretCredential->setUsername('deviceSecretCredential');
        $deviceSecretCredential->setPassword('deviceSecretCredential');
        $deviceSecretCredential->setSalt('deviceSecretCredential');
        $deviceSecretCredential->setRoleDeviceSecretCredential(true);
        $deviceSecretCredential->setEnabled(true);
        $manager->persist($deviceSecretCredential);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['prod', 'user:initialize', 'test'];
    }
}
