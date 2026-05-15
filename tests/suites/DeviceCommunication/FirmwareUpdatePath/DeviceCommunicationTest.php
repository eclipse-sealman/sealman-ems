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

namespace Tests\Suites\DeviceCommunication\FirmwareUpdatePath;

use App\DataFixtures as ProductionFixtures;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Enum\FirmwareVersionSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\DeviceCommunicationAbstract\AbstractDeviceCommunicationTestCase;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;
use Tests\Utilities\WebApi\FirmwareUpdatePath\FirmwareUpdatePathTrait;

#[Group('full')]
#[Group('smoke')]
#[Group('Feature')] // Added to Feature group as it is quick test - to be used in CI parallel tests
class DeviceCommunicationTest extends AbstractDeviceCommunicationTestCase
{
    use FirmwareUpdatePathTrait;

    public static function getFixtureGroups(): array
    {
        return [
            ProductionFixtures\ConfigurationFixtures::class,
            ProductionFixtures\UserFixtures::class,
            ProductionFixtures\DeviceTypeFixtures::class,
            ProductionFixtures\DeviceAuthenticationFixtures::class,
        ];
    }

    /**
     * Tests ANY_SCHEMA firmware schema via device communication endpoint.
     */
    #[DataProvider('featuresProvider')]
    public function testAnySchema(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'Firmware 1.0.0',
                'feature' => $feature,
                ]
            );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $firmwareUrl = $this->getResponseValue('externalUrl');
        $this->assertNotNull($firmwareUrl);

        $deviceId = $this->createDeviceWithProductionTemplateWithFirmwareWithReinstallFlag($firmwareId, $feature);

        $deviceIdentifier = $this->getResponseValue('serialNumber');
        $this->assertNotNull($deviceIdentifier);

        $this->provideDeviceTypeModel($deviceType, $deviceIdentifier);

        $parameters = [
            'agentVersion' => '1.0',
            'pySdkPackageVersion' => '1.0',
            'Firmware' => '1.0',
        ];
        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::FIRMWARE_RESPONSE,
            uriParameters: [
                'deviceEndpointSuffix' => '1' == $feature ? 'config' : 'device-supervisor-config', // as another endpoint
            ],
            parameters: $parameters,
            variables: [
                'expectedFirmwareUrl' => $firmwareUrl,
            ],
        );
    }

    /**
     * Tests other than ANY_SCHEMA firmware schema without required firmware via device communication endpoint.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testSchemaNoRequiredFirmware(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Tested in another test.', 'Tested in another test.');
            // Tested in another test. - as any version is valid
            return;
        }

        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Edit device type and set schema
        $this->getApiClient()->request(
            uri: '/web/api/devicetype/{id}',
            method: 'POST',
            uriParameters: [
                'id' => $deviceType->getId(),
            ],
            parameters: array_merge($this->getDeviceTypeEditValues($deviceType), [
                'firmwareSchema'.$feature => $firmwareSchema->value,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
                ]
            );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $firmwareUrl = $this->getResponseValue('externalUrl');
        $this->assertNotNull($firmwareUrl);

        $deviceId = $this->createDeviceWithProductionTemplateWithFirmwareWithReinstallFlag($firmwareId, $feature);

        $deviceIdentifier = $this->getResponseValue('serialNumber');
        $this->assertNotNull($deviceIdentifier);

        $this->provideDeviceTypeModel($deviceType, $deviceIdentifier);

        $parameters = [
            'agentVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 0),
            'pySdkPackageVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 0),
            'Firmware' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 0),
        ];

        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::FIRMWARE_RESPONSE,
            uriParameters: [
                'deviceEndpointSuffix' => '1' == $feature ? 'config' : 'device-supervisor-config', // as another endpoint
            ],
            parameters: $parameters,
            variables: [
                'expectedFirmwareUrl' => $firmwareUrl,
            ],
        );
    }

    /**
     * Tests other than ANY_SCHEMA firmware schema without required firmware via device communication endpoint.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testSchemaWithFirmwareUpdatePath(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Tested in another test.', 'Tested in another test.');
            // Tested in another test. - as any version is valid
            return;
        }

        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Edit device type and set schema
        $this->getApiClient()->request(
            uri: '/web/api/devicetype/{id}',
            method: 'POST',
            uriParameters: [
                'id' => $deviceType->getId(),
            ],
            parameters: array_merge($this->getDeviceTypeEditValues($deviceType), [
                'firmwareSchema'.$feature => $firmwareSchema->value,
            ])
        );

        $firmwares = $this->createFirmwareUpdatePathChain($feature, $firmwareSchema, [1, 3, 5]);

        $firmwareUrls = [];
        foreach ($firmwares as $index => $firmwareId) {
            $firmware = $this->getRepository(Firmware::class)->find($firmwareId);
            $this->assertNotNull($firmware);
            // Collect firmware urls for asserts
            $firmwareUrls[$index] = $firmware->getExternalUrl();
        }

        $deviceId = $this->createDeviceWithProductionTemplateWithFirmwareWithReinstallFlag($firmwares[5], $feature);

        $deviceIdentifier = $this->getResponseValue('serialNumber');
        $this->assertNotNull($deviceIdentifier);

        $this->provideDeviceTypeModel($deviceType, $deviceIdentifier);

        // Expecting L1 firmware
        $parameters = [
            'agentVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 0),
            'pySdkPackageVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 0),
            // As required by customer in firmwareUpdatePath we do not allow or process additional v in front of firmware version if V_SEMANTIC_VERSIONING is not used.
            // Functionality of additional v omiting was required very long time ago for backward compatibility with routers firmwares.
            'Firmware' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 0),
        ];

        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::FIRMWARE_RESPONSE,
            uriParameters: [
                'deviceEndpointSuffix' => '1' == $feature ? 'config' : 'device-supervisor-config', // as another endpoint
            ],
            parameters: $parameters,
            variables: [
                'expectedFirmwareUrl' => $firmwareUrls[1],
            ],
        );

        // Expecting L3 firmware
        $parameters = [
            'agentVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
            'pySdkPackageVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
            // NO v testing - as required by customer
            'Firmware' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
        ];

        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::FIRMWARE_RESPONSE,
            uriParameters: [
                'deviceEndpointSuffix' => '1' == $feature ? 'config' : 'device-supervisor-config', // as another endpoint
            ],
            parameters: $parameters,
            variables: [
                'expectedFirmwareUrl' => $firmwareUrls[3],
            ],
        );

        // Expecting L5 firmware
        $parameters = [
            'agentVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 4),
            'pySdkPackageVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 4),
            // As required by customer in firmwareUpdatePath we do not allow or process additional v in front of firmware version if V_SEMANTIC_VERSIONING is not used.
            // Functionality of additional v omiting was required very long time ago for backward compatibility with routers firmwares.
            'Firmware' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 4),
        ];

        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::FIRMWARE_RESPONSE,
            uriParameters: [
                'deviceEndpointSuffix' => '1' == $feature ? 'config' : 'device-supervisor-config', // as another endpoint
            ],
            parameters: $parameters,
            variables: [
                'expectedFirmwareUrl' => $firmwareUrls[5],
            ],
        );
    }

    protected function provideDeviceTypeModel(DeviceType $deviceType, string $deviceIdentifier): void
    {
        $this->getApiClient()->provide(
            DeviceTypeModel::class,
            [
                'id' => $deviceType->getId(),
                'deviceType' => $deviceType,
                'deviceTypePrefix' => 'tk600',
                'deviceTypeSecrets' => false,
                'deviceTypeCertificates' => false,
                'deviceTypeDeviceName' => $deviceType->getDeviceName(),
                'deviceIdentifier' => $deviceIdentifier,
                'deviceTypeRoutePrefix' => $deviceType->getRoutePrefix(),
                'deviceTypeCommunicationAuthenticationMethod' => $deviceType->getAuthenticationMethod(),
                'username' => 'router',
                'password' => '123456',
            ]
        );
    }
}
