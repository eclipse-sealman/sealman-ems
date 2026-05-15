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

namespace Tests\DataFixtures\Device;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DeviceFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use DeviceCommunicationFactoryTrait;

    public function load(ObjectManager $manager): void
    {
        $deviceTypeRouterTk800 = $this->getReference(ProdFixtures\DeviceTypeFixtures::DEVICETYPE_ROUTERTK800_REFERENCE, DeviceType::class);
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceTypeRouterTk800);

        $device1 = new Device();
        $device1->setDeviceType($deviceTypeRouterTk800);
        $device1->setName('Test Fixtures Router TK800 1');
        $device1->setSerialNumber('TFRTK8001');
        $device1->setIdentifier($communicationProcedure->generateIdentifier($device1));
        $device1->setUuid($communicationProcedure->getDeviceTypeUniqueUuid());
        $device1->setHashIdentifier($communicationProcedure->getDeviceUniqueHashIdentifier());
        $device1->setVirtualSubnetCidr($deviceTypeRouterTk800->getVirtualSubnetCidr());
        $device1->setMasqueradeType($deviceTypeRouterTk800->getMasqueradeType());
        $manager->persist($device1);

        $device2 = new Device();
        $device2->setDeviceType($deviceTypeRouterTk800);
        $device2->setName('Test Fixtures Router TK800 2');
        $device2->setSerialNumber('TFRTK8002');
        $device2->setIdentifier($communicationProcedure->generateIdentifier($device2));
        $device2->setUuid($communicationProcedure->getDeviceTypeUniqueUuid());
        $device2->setHashIdentifier($communicationProcedure->getDeviceUniqueHashIdentifier());
        $device2->setVirtualSubnetCidr($deviceTypeRouterTk800->getVirtualSubnetCidr());
        $device2->setMasqueradeType($deviceTypeRouterTk800->getMasqueradeType());
        $manager->persist($device2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }
}
