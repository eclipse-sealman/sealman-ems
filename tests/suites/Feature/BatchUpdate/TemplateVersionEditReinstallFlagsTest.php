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
use App\Entity\Device;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Enum\AuditLogChangeType;
use App\Enum\TemplateVersionType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Feature\AuditLog\AssertAuditLogTrait;

/**
 * Verifies changes to devices reinstallConfig1 flag and created audit logs when:
 * - Editing staging template version (editing production template version is not allowed)
 * - Selecting template version as staging
 * - Selecting template version as production.
 *
 * Logic described in App\Controller\Api\TemplateVersionController::updateReinstallFlags()
 * Setup described in fixtures.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Feature')] // To be used in CI parallel tests
class TemplateVersionEditReinstallFlagsTest extends AbstractTestCase
{
    use AssertAuditLogTrait;

    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\Feature\BatchUpdate\TemplateVersionEditReinstallFlagsFixtures::class,
        ];
    }

    public static function editStagingProvider(): iterable
    {
        $cases = [];

        $cases[] = [
            'Template 1 staging unselected',
            [],
        ];
        $cases[] = [
            'Template 2 staging unselected',
            [],
        ];
        $cases[] = [
            'Template 2 staging selected',
            ['Device with template 2, staging, flag = false'],
        ];

        // TODO Use rename when merged
        // $cases = TestParameter::rename($cases, function (array $case, int $index, int|string $name) {
        //     [$templateVersionName, $modifiedDeviceNames] = $case;

        //     return "Editing template version '{$templateVersionName}' with reinstallConfig1 = true. ".static::renameModifiedDeviceNames($modifiedDeviceNames);
        // });

        return $cases;
    }

    public static function selectStagingProvider(): iterable
    {
        $cases = [];

        $cases[] = [
            'Template 1 staging unselected',
            [],
        ];
        $cases[] = [
            'Template 2 staging unselected',
            ['Device with template 2, staging, flag = false'],
        ];
        $cases[] = [
            'Template 3 staging unselected',
            ['Device with template 3, staging, flag = false'],
        ];

        // TODO Use rename when merged
        // $cases = TestParameter::rename($cases, function (array $case, int $index, int|string $name) {
        //     [$templateVersionName, $modifiedDeviceNames] = $case;

        //     return "Selecting template version '{$templateVersionName}' as staging with reinstallConfig1 = true. ".static::renameModifiedDeviceNames($modifiedDeviceNames);
        // });

        return $cases;
    }

    public static function selectProductionProvider(): iterable
    {
        $cases = [];

        $cases[] = [
            'Template 1 production unselected',
            [],
        ];
        $cases[] = [
            'Template 2 staging selected',
            ['Device with template 2, staging, flag = false', 'Device with template 2, production, flag = false'],
        ];
        $cases[] = [
            'Template 3 production unselected',
            ['Device with template 3, staging, flag = false', 'Device with template 3, production, flag = false'],
        ];

        // TODO Use rename when merged
        // $cases = TestParameter::rename($cases, function (array $case, int $index, int|string $name) {
        //     [$templateVersionName, $modifiedDeviceNames] = $case;

        //     return "Selecting template version '{$templateVersionName}' as production with reinstallConfig1 = true. ".static::renameModifiedDeviceNames($modifiedDeviceNames);
        // });

        return $cases;
    }

    public static function renameModifiedDeviceNames(array $modifiedDeviceNames): string
    {
        if (0 === count($modifiedDeviceNames)) {
            return 'Expecting no changes in devices reinstallConfig1';
        }

        return 'Expecting changed reinstallConfig1 in devices: '.implode(', ', $modifiedDeviceNames);
    }

    public static function getInitialFlags(): array
    {
        return [
            'Device without template, staging, flag = true' => true,
            'Device without template, production, flag = false' => false,
            'Device without template, production, flag = true' => true,
            'Device with template 2, staging, flag = false' => false,
            'Device with template 2, staging, flag = true' => true,
            'Device with template 2, production, flag = false' => false,
            'Device with template 2, production, flag = true' => true,
            'Device with template 3, staging, flag = false' => false,
            'Device with template 3, staging, flag = true' => true,
            'Device with template 3, production, flag = false' => false,
            'Device with template 3, production, flag = true' => true,
        ];
    }

    #[DataProvider('editStagingProvider')]
    public function testEditStaging(string $templateVersionName, array $modifiedDeviceNames): void
    {
        $this->loginApi('admin', 'admin');

        $templateVersion = $this->getRepository(TemplateVersion::class)->findOneBy(['name' => $templateVersionName]);
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/{id}',
            uriParameters: [
                'id' => $templateVersion->getId(),
            ],
            method: 'GET',
        );

        /*
         * Edit template version with reinstallConfig1 = false. Expect:
         * - No audit logs for template version edit
         * - No audit logs for devices
         * - No changes in devices flags
         */
        static::removeAuditLogs();
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/{id}',
            method: 'POST',
            parameters: [
                'reinstallConfig1' => false,
            ],
        );
        $this->assertChangeCount(0);
        $this->assertFlags(static::getInitialFlags());

        /*
         * Edit template version with reinstallConfig1 = true. Expect:
         * - No audit logs for template version edit
         * - Audit logs for devices
         * - Changes in devices flags
         */
        static::removeAuditLogs();
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/{id}',
            method: 'POST',
            parameters: [
                'reinstallConfig1' => true,
            ],
        );
        $this->assertChangeCount(count($modifiedDeviceNames));
        $flags = static::getInitialFlags();
        foreach ($modifiedDeviceNames as $deviceName) {
            $flags[$deviceName] = true;
            $this->assertDeviceAuditLogChange($deviceName);
        }
        $this->assertFlags($flags);
    }

    #[DataProvider('selectStagingProvider')]
    public function testSelectStagingFalse(string $templateVersionName, array $modifiedDeviceNames): void
    {
        $this->loginApi('admin', 'admin');

        $templateVersion = $this->getRepository(TemplateVersion::class)->findOneBy(['name' => $templateVersionName]);

        /*
         * Select template version as staging with reinstallConfig1 = false. Expect:
         * - Audit logs for template edit
         * - No audit logs for devices
         * - No changes in devices flags
         */
        static::removeAuditLogs();
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/staging/{id}',
            uriParameters: [
                'id' => $templateVersion->getId(),
            ],
            method: 'POST',
            parameters: [
                'reinstallConfig1' => false,
            ],
        );
        $this->assertChangeCount(1);
        $this->findChange(Template::class, $templateVersion->getTemplate()->getId());
        $this->assertFlags(static::getInitialFlags());
    }

    #[DataProvider('selectStagingProvider')]
    public function testSelectStagingTrue(string $templateVersionName, array $modifiedDeviceNames): void
    {
        $this->loginApi('admin', 'admin');

        $templateVersion = $this->getRepository(TemplateVersion::class)->findOneBy(['name' => $templateVersionName]);

        /*
         * Select template version as staging with reinstallConfig1 = true. Expect:
         * - Audit logs for template edit
         * - Audit logs for devices
         * - Changes in devices flags
         */
        static::removeAuditLogs();
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/staging/{id}',
            uriParameters: [
                'id' => $templateVersion->getId(),
            ],
            method: 'POST',
            parameters: [
                'reinstallConfig1' => true,
            ],
        );
        $this->assertChangeCount(count($modifiedDeviceNames) + 1);
        $this->findChange(Template::class, $templateVersion->getTemplate()->getId());
        $flags = static::getInitialFlags();
        foreach ($modifiedDeviceNames as $deviceName) {
            $flags[$deviceName] = true;
            $this->assertDeviceAuditLogChange($deviceName);
        }
        $this->assertFlags($flags);
    }

    #[DataProvider('selectProductionProvider')]
    public function testSelectProductionFalse(string $templateVersionName, array $modifiedDeviceNames): void
    {
        $this->loginApi('admin', 'admin');

        $templateVersion = $this->getRepository(TemplateVersion::class)->findOneBy(['name' => $templateVersionName]);
        $isStaging = TemplateVersionType::STAGING === $templateVersion->getType();

        /*
         * Select template version as production with reinstallConfig1 = false. Expect:
         * - Audit logs for template edit
         * - Audit logs for template version edit ONLY with template version of type staging
         * - No audit logs for devices
         * - No changes in devices flags
         */
        static::removeAuditLogs();
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/production/{id}',
            uriParameters: [
                'id' => $templateVersion->getId(),
            ],
            method: 'POST',
            parameters: [
                'reinstallConfig1' => false,
            ],
        );
        $this->assertChangeCount($isStaging ? 2 : 1);
        $this->findChange(Template::class, $templateVersion->getTemplate()->getId());
        if ($isStaging) {
            $this->findChange(TemplateVersion::class, $templateVersion->getId());
        }
        $this->assertFlags(static::getInitialFlags());
    }

    #[DataProvider('selectProductionProvider')]
    public function testSelectProductionTrue(string $templateVersionName, array $modifiedDeviceNames): void
    {
        $this->loginApi('admin', 'admin');

        $templateVersion = $this->getRepository(TemplateVersion::class)->findOneBy(['name' => $templateVersionName]);
        $isStaging = TemplateVersionType::STAGING === $templateVersion->getType();

        /*
         * Select template version as production with reinstallConfig1 = true. Expect:
         * - Audit logs for template version edit
         * - Audit logs for devices
         * - Changes in devices flags
         */
        static::removeAuditLogs();
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/production/{id}',
            uriParameters: [
                'id' => $templateVersion->getId(),
            ],
            method: 'POST',
            parameters: [
                'reinstallConfig1' => true,
            ],
        );
        $this->assertChangeCount(count($modifiedDeviceNames) + ($isStaging ? 2 : 1));
        $this->findChange(Template::class, $templateVersion->getTemplate()->getId());
        if ($isStaging) {
            $this->findChange(TemplateVersion::class, $templateVersion->getId());
        }

        $flags = static::getInitialFlags();
        foreach ($modifiedDeviceNames as $deviceName) {
            $flags[$deviceName] = true;
            $this->assertDeviceAuditLogChange($deviceName);
        }
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
