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

use App\Entity\DeviceType;
use App\Entity\User;
use App\Entity\UserDeviceType;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DeviceAuthenticationFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
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
        $this->addDeviceAuthenticationUser($manager, 'router', '123456', ['TK800', 'TK600', 'TK100', 'TK500', 'TK500v2', 'TK500v3']);
        $this->addDeviceAuthenticationUser($manager, 'edgeGateway', '123456', ['Edge gateway', 'Edge gateway with VPN Container Client']);
        $this->addDeviceAuthenticationUser($manager, 'vpnContainerClient', '123456', ['VPN Container Client']);
        $this->addDeviceAuthenticationUser($manager, 'sgGateway', '123456', ['SG-gateway']);

        $manager->flush();
    }

    protected function addDeviceAuthenticationUser(ObjectManager $manager, string $userName, string $password, array $deviceTypeNames): void
    {
        $user = new User();
        $user->setUsername($userName);
        $user->setRoleDevice(true);
        $user->setEnabled(true);
        $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $manager->persist($user);

        foreach ($deviceTypeNames as $deviceTypeName) {
            $userDeviceType = new UserDeviceType();
            $userDeviceType->setDeviceType($manager->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]));
            $userDeviceType->setUser($user);
            $userDeviceType->setUserRole(UserRole::DEVICE);
            $manager->persist($userDeviceType);
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            DeviceTypeFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['prod', 'deviceAuthentication:initialize'];
    }
}
