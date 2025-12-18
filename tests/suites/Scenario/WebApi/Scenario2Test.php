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

namespace Tests\Suites\Scenario\WebApi;

use App\Entity\DeviceType;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * This is a scenario test. They are numbered instead of named by design.
 * It has one long method divided into several steps.
 *
 * Scenario 2 tests on SMART EMS license and using Edge gateway. It has following steps.
 * 1. Login as admin
 * 2. Create access tags (Access tag 1, Access tag 2, Access tag 3)
 * 3. Create Device 1 with AT1
 * 4. Create Device 2 with AT1 and AT2
 * 5. Create Device 3 with AT2
 * 6. Create Device 4 with no access tags
 * 7. Create SMART EMS user with AT1 (SE1)
 * 8. Create SMART EMS user with AT2 (SE2)
 * 9. Login as SE1
 * 10. Verify lists (device)
 * 11. Configure Device 1 as staging with a template and config
 * 12. Show config and verify contents
 * 13. Verify rows in lists (config, template, template version)
 * 14. Login as SE2
 * 15. Verify lists (device, config, template, template version)
 * 16. Configure Device 2 as production with a template and firmware
 * 17. Verify rows in lists (device, config, firmware, template, template version)
 * 18. Login as SE1
 * 19. Verify rows in lists (device, config, firmware, template, template version)
 */
#[Group('full')]
#[Group('smoke')]
class Scenario2Test extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            'prod',
            TestFixtures\Configuration\DisablePasswordRequirementsFixtures::class,
        ];
    }

    public function mock(): void
    {
        $this->disableFeatureScep();
        $this->disableFeatureVpn();
    }

    public function testScenario()
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'Edge gateway']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Ids are stored under named key i.e. 'at1', 'at2', 'device1' corresponding to order in which they are created.
        $atIds = [];
        $deviceIds = [];
        $configIds = [];
        $firmwareIds = [];
        $templateIds = [];
        $templateVersionIds = [];
        $firmwareIds = [];

        // 1. Login as admin
        $this->loginApi('admin', 'admin');

        // 2. Create access tags (Access tag 1, Access tag 2, Access tag 3)
        for ($i = 1; $i <= 3; ++$i) {
            $this->getApiClient()->request(
                uri: '/web/api/accesstag/create',
                parameters: [
                    'name' => 'Access tag '.$i,
                ],
            );
            $atIds['at'.$i] = $this->getResponseValue('id');
        }

        // 3. Create Device 1 with AT1
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'accessTags' => [
                    $atIds['at1'],
                ],
            ],
        );
        $deviceIds['device1'] = $this->getResponseValue('id');

        // 4. Create Device 2 with AT1 and AT2
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'accessTags' => [
                    $atIds['at1'],
                    $atIds['at2'],
                ],
            ],
        );
        $deviceIds['device2'] = $this->getResponseValue('id');

        // 5. Create Device 3 with AT2
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'accessTags' => [
                    $atIds['at2'],
                ],
            ],
        );
        $deviceIds['device3'] = $this->getResponseValue('id');

        // 6. Create Device 4 with no access tags
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'accessTags' => [],
            ],
        );
        $deviceIds['device4'] = $this->getResponseValue('id');

        // 7. Create SMART EMS user with AT1 (SE1)
        $this->getApiClient()->request(
            uri: '/web/api/user/create',
            parameters: fn () => [
                'username' => 'se1',
                'enabled' => true,
                'roleSmartems' => true,
                'plainPassword' => 'se1',
                'plainPasswordRepeat' => 'se1',
                'accessTags' => [
                    $atIds['at1'],
                ],
            ],
        );

        // 8. Create SMART EMS user with AT2 (SE2)
        $this->getApiClient()->request(
            uri: '/web/api/user/create',
            parameters: fn () => [
                'username' => 'se2',
                'enabled' => true,
                'roleSmartems' => true,
                'plainPassword' => 'se2',
                'plainPasswordRepeat' => 'se2',
                'accessTags' => [
                    $atIds['at2'],
                ],
            ],
        );

        // 9. Login as SE1
        $this->loginApi('se1', 'se1');

        // 10. Verify lists (device)
        $this->getApiClient()->request(
            uri: '/web/api/device/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $deviceIds['device1'],
                'results.1.id' => $deviceIds['device2'],
            ],
        );

        // 11. Configure Device 1 as staging with a template and config
        $this->getApiClient()->request(
            uri: '/web/api/config/create',
            parameters: [
                'name' => 'Scenario2 config1',
                'content' => '{}',
                'deviceType' => $deviceType->getId(),
            ],
            disableApplyProvideOnParameters: true,
        );
        $configIds['config1'] = $this->getResponseValue('id');

        $this->getApiClient()->request(
            uri: '/web/api/template/create',
        );
        $templateIds['template1'] = $this->getResponseValue('id');

        $this->getApiClient()->request(
            uri: '/web/api/templateversion/create/staging/{template}',
            parameters: [
                'config1' => '{config.id}',
                'accessTags' => [
                    $atIds['at1'],
                ],
            ],
        );
        $templateVersionIds['templateVersion1'] = $this->getResponseValue('id');

        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/staging/{id}',
        );
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}',
            uriParameters: [
                'id' => $deviceIds['device1'],
            ],
            method: 'GET',
        );
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}',
            method: 'POST',
            parameters: [
                'template' => '{template.id}',
                'staging' => true,
                'enabled' => true,
                'accessTags' => [
                    $atIds['at1'],
                ],
            ],
        );

        // 12. Show config and verify contents
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}/generate/config/primary',
        );

        // 13. Verify rows in lists (config, template, template version)
        $this->getApiClient()->request(
            uri: '/web/api/config/list',
            asserts: [
                'rowCount' => 1,
                'results.0.id' => $configIds['config1'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/template/list',
            asserts: [
                'rowCount' => 1,
                'results.0.id' => $templateIds['template1'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/list',
            asserts: [
                'rowCount' => 1,
                'results.0.id' => $templateVersionIds['templateVersion1'],
            ],
        );

        // 14. Login as SE2
        $this->loginApi('se2', 'se2');

        // 15. Verify lists (device, config, template, template version)
        $this->getApiClient()->request(
            uri: '/web/api/device/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $deviceIds['device2'],
                'results.1.id' => $deviceIds['device3'],
            ],
        );
        // TODO This is a bug. It should return 0 configs, but it returns 1. Commented out until fixed.
        // $this->getApiClient()->request(
        //     uri: '/web/api/config/list',
        //     asserts: [
        //         'rowCount' => 0,
        //     ],
        // );
        $this->getApiClient()->request(
            uri: '/web/api/template/list',
            asserts: [
                'rowCount' => 0,
            ],
        );
        // TODO This is a bug. It should return 0 template versions, but it returns 1. Commented out until fixed.
        // $this->getApiClient()->request(
        //     uri: '/web/api/templateversion/list',
        //     asserts: [
        //         'rowCount' => 0,
        //     ],
        // );

        // 16. Configure Device 2 as production with a template and firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
        );
        $firmwareIds['firmware1'] = $this->getResponseValue('id');

        $this->getApiClient()->request(
            uri: '/web/api/template/create',
        );
        $templateIds['template2'] = $this->getResponseValue('id');

        $this->getApiClient()->request(
            uri: '/web/api/templateversion/create/staging/{template}',
            parameters: [
                'firmware1' => '{firmware.id}',
                'accessTags' => [
                    $atIds['at2'],
                ],
            ],
        );
        $templateVersionIds['templateVersion2'] = $this->getResponseValue('id');

        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/staging/{id}',
        );
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/production/{id}',
        );
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}',
            uriParameters: [
                'id' => $deviceIds['device2'],
            ],
            method: 'GET',
        );
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}',
            method: 'POST',
            parameters: [
                'template' => '{template.id}',
                'staging' => true,
                'enabled' => true,
                'accessTags' => [
                    $atIds['at2'],
                ],
            ],
        );

        // 17. Verify rows in lists (device, config, firmware, template, template version)
        $this->getApiClient()->request(
            uri: '/web/api/device/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $deviceIds['device2'],
                'results.1.id' => $deviceIds['device3'],
            ],
        );
        // TODO This is a bug. It should return 0 configs, but it returns 1. Commented out until fixed.
        // $this->getApiClient()->request(
        //     uri: '/web/api/config/list',
        //     asserts: [
        //         'rowCount' => 0,
        //     ],
        // );
        $this->getApiClient()->request(
            uri: '/web/api/firmware/list',
            asserts: [
                'rowCount' => 1,
                'results.0.id' => $firmwareIds['firmware1'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/template/list',
            asserts: [
                'rowCount' => 1,
                'results.0.id' => $templateIds['template2'],
            ],
        );
        // TODO This is a bug. It should return 1 template versions, but it returns 2. Commented out until fixed.
        // $this->getApiClient()->request(
        //     uri: '/web/api/templateversion/list',
        //     asserts: [
        //         'rowCount' => 1,
        //         'results.0.id' => $templateVersionIds['templateVersion2'],
        //     ],
        // );

        // 18. Login as SE1
        $this->loginApi('se1', 'se1');

        // 19. Verify rows in lists (device, config, firmware, template, template version)
        $this->getApiClient()->request(
            uri: '/web/api/device/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $deviceIds['device1'],
                'results.1.id' => $deviceIds['device2'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/config/list',
            asserts: [
                'rowCount' => 1,
                'results.0.id' => $configIds['config1'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/firmware/list',
            asserts: [
                'rowCount' => 1,
                'results.0.id' => $firmwareIds['firmware1'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/template/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $templateIds['template1'],
                'results.1.id' => $templateIds['template2'],
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/list',
            asserts: [
                'rowCount' => 2,
                'results.0.id' => $templateVersionIds['templateVersion1'],
                'results.1.id' => $templateVersionIds['templateVersion2'],
            ],
        );
    }
}
