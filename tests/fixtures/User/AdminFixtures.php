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

namespace Tests\DataFixtures\User;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends AbstractFixtureGroupAsClass
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
        $admin1 = new User();
        $admin1->setUsername('admin-fixtures');
        $admin1->setRoleAdmin(true);
        $admin1->setEnabled(true);
        $admin1->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $admin1->setPassword($this->hasher->hashPassword($admin1, $admin1->getUsername()));
        $manager->persist($admin1);

        $admin2 = new User();
        $admin2->setUsername('admin2-fixtures');
        $admin2->setRoleAdmin(true);
        $admin2->setEnabled(true);
        $admin2->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $admin2->setPassword($this->hasher->hashPassword($admin2, $admin2->getUsername()));
        $manager->persist($admin2);

        $adminCt = new User();
        $adminCt->setUsername('admin-ct-fixtures');
        $adminCt->setRoleAdmin(true);
        $adminCt->setEnabled(false);
        $adminCt->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $adminCt->setPassword($this->hasher->hashPassword($adminCt, $adminCt->getUsername()));
        $manager->persist($adminCt);

        $manager->flush();
    }
}
