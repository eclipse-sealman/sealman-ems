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

class UserFixtures extends Fixture implements FixtureGroupInterface
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
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setRoleAdmin(true);
        $admin->setEnabled(true);
        $admin->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $admin->setPassword($this->hasher->hashPassword($admin, $admin->getUsername()));
        $manager->persist($admin);

        $system = new User();
        $system->setUsername('system');
        $system->setPassword('system');
        $system->setSalt('system');
        $system->setRoleSystem(true);
        $manager->persist($system);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['prod', 'user:initialize', 'test'];
    }
}
