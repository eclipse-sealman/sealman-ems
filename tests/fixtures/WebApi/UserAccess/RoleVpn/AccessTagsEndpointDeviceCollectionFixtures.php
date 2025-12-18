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

namespace Tests\DataFixtures\WebApi\UserAccess\RoleVpn;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\AccessTag;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\User;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccessTagsEndpointDeviceCollectionFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use DeviceCommunicationFactoryTrait;

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
        $vpnUser = $this->createRoleVpnUser($manager, 'vpn');
        $vpnUser->setRoleVpnEndpointDevices(true);

        $device = $this->createDevice($manager, 'd');

        $accessTagA = $this->createAccessTag($manager, 'a');
        $accessTagB = $this->createAccessTag($manager, 'b');
        $accessTagC = $this->createAccessTag($manager, 'c');
        $accessTagD = $this->createAccessTag($manager, 'd');
        $accessTagE = $this->createAccessTag($manager, 'e');

        $device->getAccessTags()->add($accessTagB);
        $device->getAccessTags()->add($accessTagC);
        $device->getAccessTags()->add($accessTagD);

        $vpnUser->getAccessTags()->add($accessTagB);
        $vpnUser->getAccessTags()->add($accessTagC);
        $vpnUser->getAccessTags()->add($accessTagD);

        $manager->flush();
    }

    protected function createDevice(ObjectManager $manager, string $name): Device
    {
        $deviceType = $this->getReference(ProdFixtures\DeviceTypeFixtures::DEVICETYPE_ROUTERTK800_REFERENCE, DeviceType::class);
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);

        $device = new Device();
        $device->setDeviceType($deviceType);
        $device->setName($name);
        $device->setSerialNumber($name);
        $device->setIdentifier($communicationProcedure->generateIdentifier($device));
        $device->setUuid($communicationProcedure->getDeviceTypeUniqueUuid());
        $device->setHashIdentifier($communicationProcedure->getDeviceUniqueHashIdentifier());
        $device->setVirtualSubnetCidr($deviceType->getVirtualSubnetCidr());
        $device->setMasqueradeType($deviceType->getMasqueradeType());

        $manager->persist($device);

        return $device;
    }

    protected function createRoleVpnUser(ObjectManager $manager, string $username): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $user->setPassword($this->hasher->hashPassword($user, $user->getUsername()));
        $user->setRoleVpn(true);
        $user->setEnabled(true);

        $manager->persist($user);

        return $user;
    }

    protected function createAccessTag(ObjectManager $manager, string $name): AccessTag
    {
        $accessTag = new AccessTag();
        $accessTag->setName($name);

        $manager->persist($accessTag);

        return $accessTag;
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }
}
