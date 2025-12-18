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

namespace Tests\DataFixtures\WebApi\UserAccess\RoleVpn\DeviceEndpointDeviceCollection;

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
use Tests\Suites\WebApi\UserAccess\RoleVpn\DeviceEndpointDeviceCollection\UniquenessTest;
use Tests\Utilities\WebApi\UserAccess\AccessibleEndpointDevice;
use Tests\Utilities\WebApi\UserAccess\Action;
use Tests\Utilities\WebApi\UserAccess\NonAccessibleEndpointDevice;
use Tests\Utilities\WebApi\UserAccess\UniquenessTestCase;  // TODO decide if this is good approach

class UniquenessTestFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
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

        $accessTagA = $this->createAccessTag($manager, 'vpn-access');

        $vpnUser->getAccessTags()->add($accessTagA);
        $manager->flush();

        foreach (UniquenessTest::getTestCases() as $key => $testCase) {
            $device = $this->createDevice($manager, $testCase->deviceName);
            $device->getAccessTags()->add($accessTagA);

            $this->createEndpointDevice($manager, $device, $testCase->element1, $accessTagA);
            $this->createEndpointDevice($manager, $device, $testCase->element2, $accessTagA);
            $this->createEndpointDevice($manager, $device, $testCase->element3, $accessTagA);
            $this->createEndpointDevice($manager, $device, $testCase->element4, $accessTagA);
            $this->createEndpointDevice($manager, $device, $testCase->element5, $accessTagA);
        }

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

    protected function createEndpointDevice(ObjectManager $manager, Device $device, null|AccessibleEndpointDevice|NonAccessibleEndpointDevice $element, AccessTag $accessibleAccessTag)
    {
        if (null === $element) {
            return;
        }

        if ($element instanceof AccessibleEndpointDevice && Action::CREATE === $element->action) {
            return;
        }

        $key = $element->key;

        $endpointDevice = new DeviceEndpointDevice();
        $endpointDevice->setDevice($device);
        $endpointDevice->setName(UniquenessTestCase::getElementOriginalName($key));
        $endpointDevice->setPhysicalIp(UniquenessTestCase::getElementOriginalPhysicalIp($key));
        $endpointDevice->setVirtualIpHostPart(UniquenessTestCase::getElementOriginalVirtualIpHostPart($key));

        if ($element instanceof AccessibleEndpointDevice) {
            $endpointDevice->getAccessTags()->add($accessibleAccessTag);
        }

        $device->getEndpointDevices()->add($endpointDevice);

        $manager->persist($endpointDevice);
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
