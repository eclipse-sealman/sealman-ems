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

namespace Tests\Suites\Scenario\DeviceCommunication;

use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\AuthenticationMethod;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\DeviceCommunicationAbstract\AbstractDeviceCommunicationTestCase;
use Tests\Utilities\DeviceCommunicationAbstract\Trait\DeviceAuthenticationTrait;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;

/**
 * This is a device communication scenario test. They are numbered instead of named by design.
 * It has one long method divided into several steps.
 *
 * Scenario 1 tests simple device communication using TK800 without any authentication. It has following steps.
 * 1. Communicate as a device which results in creating a disabled device
 * 2. Login as admin
 * 3. Create config
 * 4. Create template
 * 5. Create template version with selected config
 * 6. Set template version as staging
 * 7. Set template version as production
 * 8. Edit device and set template and enable the device
 * 9. Communicate via WebUI as a device and verify received config
 * 10. Communicate via devicecommunication as a device and verify received config
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Scenario')] // To be used in CI parallel tests
class Scenario1Test extends AbstractDeviceCommunicationTestCase
{
    use DeviceAuthenticationTrait;

    public static function getFixtureGroups(): array
    {
        return [
            'prod',
        ];
    }

    public function testScenario()
    {
        $deviceTypeName = 'TK800';

        // 1. Communicate as a device which results in creating a disabled device
        // Provide device type authentication model with no authentication and other DeviceTypeModel values required by device communication api client
        $this->provideDeviceTypeAuthenticationModel($deviceTypeName, '', AuthenticationMethod::NONE, AuthenticationMethod::NONE, []);

        // Initial communication to create the device
        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::INITIAL,
        );

        // End of step 1.

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $configContent = <<<CONFIG
Config content
Device name {{ name }}
{% for i in 1..3 %}
config{{ i }}: {{ i * 2 }}
{% endfor %}
CONFIG;

        // 2. Login as admin
        $this->loginApi('admin', 'admin');

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

        // 4. Create template
        $this->getApiClient()->request(
            uri: '/web/api/template/create',
        );

        // 5. Create template version with selected config and firmware
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/create/staging/{template}',
            parameters: [
                'config1' => '{config.id}',
            ],
        );

        // 6. Set template version as staging
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/staging/{id}',
        );

        // 7. Set template version as production
        $this->getApiClient()->request(
            uri: '/web/api/templateversion/select/production/{id}',
        );

        // 8. Edit device and set template and enable the device

        // Get the provided device entity created in step 1
        $device = $this->getProvidedDeviceEntity();
        $this->getApiClient()->provide(Device::class, [
            'id' => $device->getId(),
            'name' => $device->getName(),
            'serialNumber' => $device->getSerialNumber(),
        ]);

        $this->getApiClient()->request(
            uri: '/web/api/device/{id}',
            method: 'POST',
            parameters: [
                'template' => '{template.id}',
                'enabled' => true,
                'reinstallConfig1' => true,
            ],
        );

        // 9. Show config and verify contents - using web api
        $this->getApiClient()->request(
            uri: '/web/api/device/{id}/generate/config/primary',
        );
        $expectedGeneratedConfig = '"Config content\nDevice name '.$device->getName().'\nconfig1: 2\nconfig2: 4\nconfig3: 6\n"';
        $response = $this->getResponseContent();
        $this->assertSame($expectedGeneratedConfig, $response);

        // 10. Show config and verify contents - using device communication api
        // Making sure that special characters in config are properly handled
        $expectedGeneratedConfig = "Config content\nDevice name ".$device->getName()."\nconfig1: 2\nconfig2: 4\nconfig3: 6\n";
        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::SUCCESSFUL_RESPONSE,
        );

        $response = $this->getResponseContent();
        $this->assertSame($expectedGeneratedConfig, $response);
    }
}
