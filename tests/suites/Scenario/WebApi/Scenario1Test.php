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
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * This is a scenario test. They are numbered instead of named by design.
 * It has one long method divided into several steps.
 *
 * Scenario 1 tests on demo license and using TK800. It has following steps.
 * 1. Login as admin
 * 2. Create device
 * 3. Create config
 * 4. Create firmware
 * 5. Create template
 * 6. Create template version with selected config and firmware
 * 7. Set template version as staging
 * 8. Set template version as production
 * 9. Edit device and set template and enable the device
 * 10. Show config and verify contents
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Scenario')] // To be used in CI parallel tests
class Scenario1Test extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            'prod',
        ];
    }

    public function testScenario()
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $configContent = <<<CONFIG
Config content
Device name {{ name }}
{% for i in 1..3 %}
config{{ i }}: {{ i * 2 }}
{% endfor %}
CONFIG;

        // 1. Login as admin
        $this->loginApi('admin', 'admin');

        // 2. Create device
        $this->getApiClient()->request(
            uri: '/web/api/device/create',
            parameters: [
                'name' => 'Scenario1 device',
            ],
        );

        // 3. Create config
        $this->getApiClient()->request(
            uri: '/web/api/config/create',
            parameters: [
                'name' => 'Scenario1 config',
                'content' => $configContent,
                'deviceType' => $deviceType->getId(),
            ],
            // Config content has provide syntax, so we need to disable it
            disableApplyProvideOnParameters: true,
        );

        // 4. Create firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
        );

        // 5. Create template
        $this->getApiClient()->request(
            uri: '/web/api/template/create',
        );

        // 6. Create template version with selected config and firmware
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/create/staging/{template}',
            parameters: [
                'config1' => '{config.id}',
                'firmware1' => '{firmware.id}',
            ],
        );

        // 7. Set template version as staging
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/staging/{id}',
        );

        // 8. Set template version as production
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/production/{id}',
        );

        // 9. Edit device and set template and enable the device
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}',
            method: 'POST',
            parameters: [
                'template' => '{template.id}',
                'enabled' => true,
            ],
        );

        // 10. Show config and verify contents
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}/generate/config/primary',
        );
        $expectedGeneratedConfig = '"Config content\nDevice name Scenario1 device\nconfig1: 2\nconfig2: 4\nconfig3: 6\n"';
        $response = $this->getResponseContent();
        $this->assertSame($expectedGeneratedConfig, $response);
    }
}
