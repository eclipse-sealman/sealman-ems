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

class UserDeviceX509CredentialsFixtures extends Fixture implements FixtureGroupInterface
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
        $deviceX509Credential = new User();
        $deviceX509Credential->setUsername('deviceX509Credential');
        $deviceX509Credential->setPassword('deviceX509Credential');
        $deviceX509Credential->setSalt('deviceX509Credential');
        $deviceX509Credential->setRoleDeviceX509Credential(true);
        $deviceX509Credential->setEnabled(true);
        $manager->persist($deviceX509Credential);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['prod', 'user:initialize', 'test'];
    }
}
