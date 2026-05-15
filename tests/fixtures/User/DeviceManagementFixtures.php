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
use App\Entity\AccessTag;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DeviceManagementFixtures extends AbstractFixtureGroupAsClass
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
        $accessTag = new AccessTag();
        $accessTag->setName('Access tag device management');
        $manager->persist($accessTag);

        $deviceManagement = new User();
        $deviceManagement->setUsername('deviceManagement');
        $deviceManagement->setRoleSmartEms(true);
        $deviceManagement->addAccessTag($accessTag);
        $deviceManagement->setEnabled(true);
        $deviceManagement->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $deviceManagement->setPassword($this->hasher->hashPassword($deviceManagement, $deviceManagement->getUsername()));
        $manager->persist($deviceManagement);

        $manager->flush();
    }
}
