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
use App\Entity\DeviceEndpointDevice;
use App\Entity\DeviceType;
use App\Entity\User;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DeviceAndEndpointDeviceAccessFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
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
        $accessTag1 = $this->createAccessTag($manager, 'at1');
        $accessTag2 = $this->createAccessTag($manager, 'at2');
        $accessTag3 = $this->createAccessTag($manager, 'at3');
        $accessTag4 = $this->createAccessTag($manager, 'at4');

        $vpnUser = $this->createRoleVpnUser($manager, 'vpnat1');
        $vpnUser->getAccessTags()->add($accessTag1);

        $vpnUser = $this->createRoleVpnUser($manager, 'vpnat3at2');
        $vpnUser->getAccessTags()->add($accessTag3);
        $vpnUser->getAccessTags()->add($accessTag2);

        $vpnUser = $this->createRoleVpnUser($manager, 'vpnat3');
        $vpnUser->getAccessTags()->add($accessTag3);

        $vpnUser = $this->createRoleVpnUser($manager, 'vpnedat1');
        $vpnUser->setRoleVpnEndpointDevices(true);
        $vpnUser->getAccessTags()->add($accessTag1);

        $vpnUser = $this->createRoleVpnUser($manager, 'vpnedat3at2');
        $vpnUser->setRoleVpnEndpointDevices(true);
        $vpnUser->getAccessTags()->add($accessTag3);
        $vpnUser->getAccessTags()->add($accessTag2);

        $vpnUser = $this->createRoleVpnUser($manager, 'vpnedat3');
        $vpnUser->setRoleVpnEndpointDevices(true);
        $vpnUser->getAccessTags()->add($accessTag3);

        $device = $this->createDevice($manager, 'dnone');

        $device = $this->createDevice($manager, 'dat4');
        $device->getAccessTags()->add($accessTag4);

        $device = $this->createDevice($manager, 'dnone-ed1none-ed2at4');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed1none');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed2at4');
        $endpointDevice->getAccessTags()->add($accessTag4);

        $device = $this->createDevice($manager, 'dat1');
        $device->getAccessTags()->add($accessTag1);

        $device = $this->createDevice($manager, 'dat3at1');
        $device->getAccessTags()->add($accessTag3);
        $device->getAccessTags()->add($accessTag1);

        $device = $this->createDevice($manager, 'dnone-ed1at1-ed2at4');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed1at1');
        $endpointDevice->getAccessTags()->add($accessTag1);
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed2at4');
        $endpointDevice->getAccessTags()->add($accessTag4);

        $device = $this->createDevice($manager, 'dnone-ed1none-ed2at1-ed3at3');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed1none');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed2at1');
        $endpointDevice->getAccessTags()->add($accessTag1);
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed3at3');
        $endpointDevice->getAccessTags()->add($accessTag3);

        $device = $this->createDevice($manager, 'dnone-ed1none-ed2at1-ed3at3at4');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed1none');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed2at1');
        $endpointDevice->getAccessTags()->add($accessTag1);
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed3at3at4');
        $endpointDevice->getAccessTags()->add($accessTag3);
        $endpointDevice->getAccessTags()->add($accessTag4);

        $device = $this->createDevice($manager, 'dat1-ed1none-ed2at1-ed3at3');
        $device->getAccessTags()->add($accessTag1);
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed1none');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed2at1');
        $endpointDevice->getAccessTags()->add($accessTag1);
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed3at3');
        $endpointDevice->getAccessTags()->add($accessTag3);

        $device = $this->createDevice($manager, 'dat4-ed1none-ed2at1-ed3at3');
        $device->getAccessTags()->add($accessTag4);
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed1none');
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed2at1');
        $endpointDevice->getAccessTags()->add($accessTag1);
        $endpointDevice = $this->createEndpointDevice($manager, $device, 'ed3at3');
        $endpointDevice->getAccessTags()->add($accessTag3);

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
        $device->setVirtualSubnetCidr(28);
        $device->setMasqueradeType($deviceType->getMasqueradeType());

        $manager->persist($device);

        return $device;
    }

    protected function createEndpointDevice(ObjectManager $manager, Device $device, string $name): DeviceEndpointDevice
    {
        $key = $device->getEndpointDevices()->count() + 1;

        $endpointDevice = new DeviceEndpointDevice();
        $endpointDevice->setDevice($device);
        $endpointDevice->setName($name);
        $endpointDevice->setPhysicalIp($key.'.'.$key.'.'.$key.'.'.$key);
        $endpointDevice->setVirtualIpHostPart($key);

        $device->getEndpointDevices()->add($endpointDevice);

        $manager->persist($endpointDevice);

        return $endpointDevice;
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
