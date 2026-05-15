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

namespace Tests\DataFixtures\CertificateTypes;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tests\DataFixtures as TestFixtures;

class DeviceFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use DeviceCommunicationFactoryTrait;

    public function load(ObjectManager $manager): void
    {
        $deviceTypeNames = [
            'Edge gateway',
            'TK800',
            'TK500',
            'VPN Container Client',
            'Edge gateway with VPN Container Client',
        ];

        foreach ($deviceTypeNames as $deviceTypeName) {
            $deviceType = $manager->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]);
            $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
            $device = new Device();
            $device->setDeviceType($deviceType);
            $device->setName($deviceType->getCertificateCommonNamePrefix().time());
            $device->setSerialNumber($deviceType->getName());
            $device->setIdentifier($communicationProcedure->generateIdentifier($device));
            $device->setUuid($communicationProcedure->getDeviceTypeUniqueUuid());
            $device->setHashIdentifier($communicationProcedure->getDeviceUniqueHashIdentifier());
            $device->setVirtualSubnetCidr($deviceType->getVirtualSubnetCidr());
            $device->setMasqueradeType($deviceType->getMasqueradeType());
            $manager->persist($device);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TestFixtures\CertificateTypes\DeviceTypeFixtures::class,
        ];
    }
}
