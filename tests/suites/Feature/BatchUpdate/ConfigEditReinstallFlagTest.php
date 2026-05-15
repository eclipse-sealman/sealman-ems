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

namespace Tests\Suites\Feature\BatchUpdate;

use App\DataFixtures as ProdFixtures;
use App\Entity\Config;
use App\Entity\Device;
use App\Enum\AuditLogChangeType;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Feature\AuditLog\AssertAuditLogTrait;

/**
 * Verifies changes to devices reinstallConfig1 flag and created audit logs when editing config with reinstallConfig = true.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Feature')] // To be used in CI parallel tests
class ConfigEditReinstallFlagTest extends AbstractTestCase
{
    use AssertAuditLogTrait;

    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\Feature\BatchUpdate\ConfigEditReinstallFlagFixtures::class,
        ];
    }

    public static function getInitialFlags(): array
    {
        return [
            'Device A with template, staging, flag = false' => false,
            'Device B with template, staging, flag = false' => false,
            'Device with template, staging, flag = true' => true,
            'Device without template, staging, flag = false' => false,
            'Device without template, staging, flag = true' => true,
            'Device without template, production, flag = false' => false,
            'Device without template, production, flag = true' => true,
        ];
    }

    /**
     * Config is a primary config and is connected to devices through templates.
     * Step 1: Update config with reinstallConfig flag set to false and expect no changes.
     * Step 2: Update config with reinstallConfig flag set to true and expect changes.
     *
     * Expected that only Device 1 and Device 2 will have reinstallConfig1 updated and have AuditLog created.
     *
     * Devices as follows:
     * - Device 1 (staging, reinstallConfig1 = false) has selected Template 1 with TV 1 (staging)
     * - Device 2 (staging, reinstallConfig1 = false) has selected Template 1 with TV 1 (staging)
     * - Device 3 (staging, reinstallConfig1 = true) has selected Template 1 with TV 1 (staging)
     * - Device 4 (staging, reinstallConfig1 = false) not connected to a template
     * - Device 5 (staging, reinstallConfig1 = true) not connected to a template
     * - Device 6 (production, reinstallConfig1 = false) not connected to a template
     * - Device 7 (production, reinstallConfig1 = true) not connected to a template
     *
     * We cannot cover:
     * - Device 8 (production, reinstallConfig1 = false) has selected Template 2 with TV 2 (production)
     * - Device 9 (production, reinstallConfig1 = true) has selected Template 2 with TV 2 (production)
     *
     * because config cannot be edited when used by production template.
     */
    public function testConfigEditReinstallFlag()
    {
        $this->getEntityManager()->clear();

        $this->loginApi('admin', 'admin');

        $config = $this->getRepository(Config::class)->findOneBy(['name' => 'Config']);
        $configId = $config->getId();
        $this->getApiClient()->provide(Config::class, [
            'id' => $configId,
        ]);

        $this->getApiClient()->request(
            uri: '/web/api/config/{id}',
            method: 'GET',
        );

        /*
         * Edit edit config with reinstallConfig1 = false. Expect:
         * - Audit logs for config edit
         * - No audit logs for devices
         * - No changes in devices flags
         */
        static::removeAuditLogs();
        $this->getApiClient()->request(
            uri: '/web/api/config/{id}',
            method: 'POST',
            parameters: [
                'content' => 'Updated content 1',
                'reinstallConfig' => false,
            ],
        );

        $this->assertChangeCount(1);
        $this->findChange(Config::class, $configId);
        $this->assertFlags(self::getInitialFlags());

        /*
         * Edit edit config with reinstallConfig1 = true. Expect:
         * - Audit logs for config edit
         * - Audit logs for devices
         * - Changes in devices flags
         */
        static::removeAuditLogs();
        $this->getApiClient()->request(
            uri: '/web/api/config/{id}',
            method: 'POST',
            parameters: [
                'content' => 'Updated content 2',
                'reinstallConfig' => true,
            ],
        );

        $this->assertChangeCount(1 + 2);
        $change = $this->findChange(Config::class, $configId);

        $flags = self::getInitialFlags();
        $flags['Device A with template, staging, flag = false'] = true;
        $this->assertDeviceAuditLogChange('Device A with template, staging, flag = false');
        $flags['Device B with template, staging, flag = false'] = true;
        $this->assertDeviceAuditLogChange('Device B with template, staging, flag = false');
        $this->assertFlags($flags);
    }

    protected function findDevice(string $deviceName): Device
    {
        $device = $this->getRepository(Device::class)->findOneBy(['name' => $deviceName]);
        $this->assertNotNull($device, "Device '{$deviceName}' not found");

        return $device;
    }

    /**
     * @param array<string, bool> $flags Key is device name, value is expected reinstallConfig1 flag value
     */
    protected function assertFlags(array $flags): void
    {
        foreach ($flags as $deviceName => $expectedReinstallConfig1) {
            $this->assertFlag($deviceName, $expectedReinstallConfig1);
        }
    }

    protected function assertFlag(string $deviceName, bool $expectedReinstallConfig1): void
    {
        $device = $this->findDevice($deviceName);

        $this->assertSame($expectedReinstallConfig1, $device->getReinstallConfig1(), "Device '{$deviceName}' has unexpected reinstallConfig1 value");
    }

    protected function assertDeviceAuditLogChange(string $deviceName): void
    {
        $device = $this->findDevice($deviceName);

        $change = $this->findChange(Device::class, $device->getId(), 'Audit log change of reinstallConfig1 flag for device "'.$deviceName.'" not found');
        $this->assertChange($change, AuditLogChangeType::UPDATE, [
            'reinstallConfig1' => false,
        ], [
            'reinstallConfig1' => true,
        ]);
    }
}
