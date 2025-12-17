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

namespace Tests\Suites\Unit\ClassMetadataFactory;

use App\DataFixtures as ProdFixtures;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\Label;
use App\Entity\VpnLog;
use App\Enum\LogLevel;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Tests App\ORM\Mapping\ClassMetadataFactory.
 *
 * Read more about its use case in mentioned class.
 *
 * This test only intends to inform us of any critical changes in Doctrine behaviour in relation to our ClassMetadataFactory.
 */
#[Group('full')]
#[Group('smoke')]
class ClassMetadataFactoryTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }

    public function testOneToManyAfterRemove()
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => 'TK800',
        ]);

        $device = new Device();
        $device->setName('ClassMetadataFactoryTest-testOneToMany');
        $device->setIdentifier('ClassMetadataFactoryTest-testOneToMany');
        $device->setVirtualSubnetCidr(30);
        $device->setDeviceType($deviceType);

        $this->getEntityManager()->persist($device);
        $this->getEntityManager()->flush();

        $deviceId = $device->getId();

        $this->getEntityManager()->detach($device);
        $this->getEntityManager()->detach($deviceType);

        $device = $this->getRepository(Device::class)->find($deviceId);
        $this->assertInstanceOf(Device::class, $device);

        $vpnLog = new VpnLog();
        $vpnLog->setMessage('VPN log for ClassMetadataFactoryTest-testOneToMany ID = '.$deviceId);
        $vpnLog->setLogLevel(LogLevel::WARNING);
        $vpnLog->setDevice($device);

        $this->getEntityManager()->persist($vpnLog);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->remove($device);
        $this->getEntityManager()->flush();

        $label = new Label();
        $label->setName('ClassMetadataFactoryTest-testOneToMany Label');

        $this->getEntityManager()->persist($label);
        $this->getEntityManager()->flush();
    }

    public function testChangeTrackingDeferredExplicitNoPersist()
    {
        $originalName = 'TK800';
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => $originalName,
        ]);
        $modifiedName = 'testChangeTrackingDeferredExplicitNoPersist';
        $deviceType->setName($modifiedName);

        $label = new Label();
        $label->setName('ClassMetadataFactoryTest-testChangeTrackingDeferredExplicitNoPersist Label');

        $this->getEntityManager()->persist($label);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();

        // Name should not be changed and it should be possible to find DeviceType by original name
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => $originalName,
        ]);
        $this->assertInstanceOf(DeviceType::class, $deviceType);

        // Name should not be changed and it should NOT be possible to find DeviceType by modified name
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => $modifiedName,
        ]);
        $this->assertNull($deviceType);
    }

    public function testChangeTrackingDeferredExplicitPersist()
    {
        $originalName = 'TK800';
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => $originalName,
        ]);
        $modifiedName = 'testChangeTrackingDeferredExplicitPersist';
        $deviceType->setName($modifiedName);
        $this->getEntityManager()->persist($deviceType);

        $label = new Label();
        $label->setName('ClassMetadataFactoryTest-testChangeTrackingDeferredExplicitPersist Label');

        $this->getEntityManager()->persist($label);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();

        // Name should be changed and it should NOT be possible to find DeviceType by original name
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => $originalName,
        ]);
        $this->assertNull($deviceType);

        // Name should be changed and it should NOT be possible to find DeviceType by modified name
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => $modifiedName,
        ]);
        $this->assertInstanceOf(DeviceType::class, $deviceType);
    }
}
