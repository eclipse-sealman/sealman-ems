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

namespace Tests\Suites\WebApi\FirmwareUpdatePath;

use App\DataFixtures as ProdFixtures;
use App\DeviceCommunication\DeviceCommunicationFactory;
use App\Entity\CommunicationLog;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Enum\Feature;
use App\Enum\FirmwareVersionSchema;
use App\Service\CommunicationLogManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Utility\Accessor;
use Tests\Utilities\WebApi\FirmwareUpdatePath\FirmwareUpdatePathTrait;

#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class ProcessFirmwareUpdatePathTest extends AbstractTestCase
{
    use FirmwareUpdatePathTrait;

    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }

    /**
     * Tests ANY_SCHEMA firmware schema with and without required firmware.
     */
    #[DataProvider('featuresProvider')]
    public function testAnySchema(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'Firmware 1.0.0',
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareId,
            firmwareVersion: 'v1.0.1',
            feature: $feature,
            firmwareSchema: FirmwareVersionSchema::ANY_SCHEMA,
            expectedResult: true,
            expectedLogMessage: 'log.deviceReinstallingFirmware',
        );

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.2',
                'feature' => $feature,
            ]
        );

        $firmwareNextId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareNextId);

        $firmware = $this->getRepository(Firmware::class)->find($firmwareId);
        $this->assertNotNull($firmware);

        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwareNextId,
            newValues: ['requiredFirmware' => $firmware]
        );

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareNextId,
            firmwareVersion: 'v1.0.1',
            feature: $feature,
            firmwareSchema: FirmwareVersionSchema::ANY_SCHEMA,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareRequiredFirmwareNotSupported',
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade allowed, without required firmware with valid version.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testNoRequiredFirmwareValidVersionDowngradeAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => true,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $version = $this->getValidFirmwareVersionByLevel($firmwareSchema, 1);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $version,
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareId,
            firmwareVersion: 'Any version',
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: true,
            expectedLogMessage: 'log.deviceReinstallingFirmwareRequiredFirmwareNotSet',
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade allowed, without required firmware with invalid version.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testNoRequiredFirmwareInvalidVersionDowngradeAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => true,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwareId,
            newValues: ['version' => 'InvalidVersion']
        );

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareId,
            firmwareVersion: 'Any version',
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: true,
            expectedLogMessage: 'log.deviceReinstallingFirmwareRequiredFirmwareNotSet',
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade allowed, with required firmware but device version is not provided.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testRequiredFirmwareNoDeviceVersionDowngradeAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => true,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
                'feature' => $feature,
                'requiredFirmware' => $firmwareId,
            ]
        );

        $firmwareNextId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareNextId);

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareNextId,
            firmwareVersion: null,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionRequired',
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade allowed, with required firmware but device version is invalid.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testRequiredFirmwareInvalidDeviceVersionDowngradeAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => true,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
                'feature' => $feature,
                'requiredFirmware' => $firmwareId,
            ]
        );

        $firmwareNextId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareNextId);

        $invalidVersion = 'InvalidVersion';
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareNextId,
            firmwareVersion: $invalidVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionInvalid',
            logVariables: ['version' => $invalidVersion]
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade not allowed, without required firmware with valid version.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testNoRequiredFirmwareValidVersionDowngradeNotAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => false,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $version = $this->getValidFirmwareVersionByLevel($firmwareSchema, 1);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $version,
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $invalidVersion = 'Invalid Version';

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareId,
            firmwareVersion: $invalidVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionInvalid',
            logVariables: ['version' => $invalidVersion]
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade not allowed, without required firmware with invalid version.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testNoRequiredFirmwareInvalidVersionDowngradeNotAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => false,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $invalidVersion = 'InvalidVersion';
        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwareId,
            newValues: ['version' => $invalidVersion]
        );

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareId,
            firmwareVersion: $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionInvalid',
            logVariables: ['version' => $invalidVersion]
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade not allowed, without required firmware but device version is higher.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testNoRequiredFirmwareHigherDeviceVersionDowngradeNotAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => false,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareId,
            firmwareVersion: $this->getValidFirmwareVersionByLevel($firmwareSchema, 3),
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareCannotBeDowngraded',
            logVariables: [
                'receivedVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 3),
                'firmwareVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
            ],
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade allowed, without required firmware but device version is higher.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testNoRequiredFirmwareHigherDeviceVersionDowngradeAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => true,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareId,
            firmwareVersion: $this->getValidFirmwareVersionByLevel($firmwareSchema, 3),
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: true,
            expectedLogMessage: 'log.deviceReinstallingFirmwareRequiredFirmwareNotSet',
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade not allowed, with required firmware but device version is not provided.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testRequiredFirmwareNoDeviceVersionDowngradeNotAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => false,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
                'feature' => $feature,
                'requiredFirmware' => $firmwareId,
            ]
        );

        $firmwareNextId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareNextId);

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareNextId,
            firmwareVersion: null,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionRequired',
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade not allowed, with required firmware but device version is invalid.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testRequiredFirmwareInvalidDeviceVersionDowngradeNotAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => false,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
                'feature' => $feature,
                'requiredFirmware' => $firmwareId,
            ]
        );

        $firmwareNextId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareNextId);

        $invalidVersion = 'InvalidVersion';
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareNextId,
            firmwareVersion: $invalidVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionInvalid',
            logVariables: ['version' => $invalidVersion]
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade not allowed, with required firmware but device version is higher.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testRequiredFirmwareHigherDeviceVersionDowngradeNotAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => false,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
                'feature' => $feature,
                'requiredFirmware' => $firmwareId,
            ]
        );

        $firmwareNextId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareNextId);

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareNextId,
            firmwareVersion: $this->getValidFirmwareVersionByLevel($firmwareSchema, 3),
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareCannotBeDowngraded',
            logVariables: [
                'receivedVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 3),
                'firmwareVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
            ],
        );
    }

    /**
     * Tests non-ANY_SCHEMA firmware schema with downgrade allowed, with required firmware but device version is higher.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testRequiredFirmwareHigherDeviceVersionDowngradeAllowed(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
                'allowDowngradeFirmware'.$feature => true,
            ])
        );

        $this->getApiClient()->request(
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
                'feature' => $feature,
                'requiredFirmware' => $firmwareId,
            ]
        );

        $firmwareNextId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareNextId);

        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwareNextId,
            firmwareVersion: $this->getValidFirmwareVersionByLevel($firmwareSchema, 3),
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: true,
            expectedLogMessage: [
                'log.deviceReinstallingFirmwareInUpdatePath',
                'log.deviceReinstallingFirmwareRequiredFirmwareAlreadyInstalled',
            ],
            logVariables: [
                ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2)],
                [
                    'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 3),
                    'requiredVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                ],
            ]
        );
    }

    /**
     * Method to test invalid required firmware scenarios - in first and second execution of the loop (first and second level of firmware update path)
     * - invalid device type in required firmware
     * - invalid feature in required firmware
     * - invalid version in required firmware
     * - version not higher in required firmware.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testInvalidRequiredFirmware(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $firmwares = $this->createFirmwareUpdatePathChain($feature, $firmwareSchema, [1, 3, 5]);

        // Testing case when L1 version is not lower than L2 version
        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwares[1],
            newValues: ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 3)]
        );

        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionNotInOrder',
            logVariables: ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 3)] // Same as in L1 firmware after update
        );

        // Testing case when L1 version is not valid version
        $invalidVersion = 'InvalidVersion';
        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwares[1],
            newValues: ['version' => $invalidVersion]
        );

        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionInvalid',
            logVariables: ['version' => $invalidVersion] // Same as in L1 firmware after update
        );

        // Testing case when L1 version is not valid feature
        $invalidFeature = $this->getOtherFeature($feature);

        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwares[1],
            newValues: ['feature' => Feature::tryFrom($invalidFeature)]
        );

        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareFeatureMismatch',
            logVariables: ['version' => $invalidVersion] // Same as in L1 firmware after update
        );

        // Testing case when L1 version is not valid device type
        $invalidDeviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);

        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwares[1],
            newValues: ['deviceType' => $invalidDeviceType]
        );

        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareDeviceTypeMismatch',
            logVariables: ['version' => $invalidVersion] // Same as in L1 firmware after update
        );

        // Testing case when L3 version is not lower than L5 version
        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwares[3],
            newValues: ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 5)]
        );

        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionNotInOrder',
            logVariables: ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 5)] // Same as in L3 firmware after update
        );

        // Testing case when L3 version is not valid version
        // Changing to different invalid version to avoid same log message as in previous test
        $invalidVersion = 'InvalidVersion2';
        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwares[3],
            newValues: ['version' => $invalidVersion]
        );

        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareVersionInvalid',
            logVariables: ['version' => $invalidVersion] // Same as in L3 firmware after update
        );

        // Testing case when L3 version is not valid feature
        $invalidFeature = $this->getOtherFeature($feature);

        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwares[3],
            newValues: ['feature' => Feature::tryFrom($invalidFeature)]
        );

        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareFeatureMismatch',
            logVariables: ['version' => $invalidVersion] // Same as in L3 firmware after update
        );

        // Testing case when L3 version is not valid device type
        $invalidDeviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);

        $this->updateEntityById(
            entityClass: Firmware::class,
            id: $firmwares[3],
            newValues: ['deviceType' => $invalidDeviceType]
        );

        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: false,
            expectedLogMessage: 'log.deviceInstallFirmwareFirmwareDeviceTypeMismatch',
            logVariables: ['version' => $invalidVersion] // Same as in L3 firmware after update
        );
    }

    /**
     * Method to test valid required firmware scenarios - in first and second execution of the loop (first and second level of firmware update path)
     * - version higher in first level required firmware
     * - version higher in second level required firmware
     * - firmware to be installed has required firmware set to null
     * - firmware to be installed has required firmware set to valid firmware.
     */
    #[DataProvider('featuresAndFirmwareSchemaProvider')]
    public function testValidRequiredFirmware(string $feature, FirmwareVersionSchema $firmwareSchema)
    {
        if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
            $this->assertSame('Skip any schema', 'Skip any schema');
            // Skip any schema - as any version is valid
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
            uri: '/web/api/device/create',
        );

        $deviceId = $this->getResponseValue('id');
        $this->assertNotNull($deviceId);

        $firmwares = $this->createFirmwareUpdatePathChain($feature, $firmwareSchema, [1, 3, 5]);

        // Testing case when L1 should be installed
        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 0);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: true,
            expectedLogMessage: [
                'log.deviceReinstallingFirmwareRequiredFirmwareNotInstalled',
                'log.deviceReinstallingFirmwareRequiredFirmwareNotInstalled',
                'log.deviceReinstallingFirmwareFirstFirmwareInUpdatePath',
            ],
            logVariables: [
                ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 3)], // Same as in L1 firmware after update
                ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1)], // Same as in L1 firmware after update
                ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1)], // Same as in L1 firmware after update
            ]
        );

        $this->removeCommunicationLogs();

        // Testing case when L3 should be installed
        $deviceFirmwareVersion = $this->getValidFirmwareVersionByLevel($firmwareSchema, 2);
        $this->executeProcessFirmwareUpdatePathTest(
            deviceType: $deviceType,
            deviceId: $deviceId,
            firmwareId: $firmwares[5],
            firmwareVersion: $deviceFirmwareVersion,
            feature: $feature,
            firmwareSchema: $firmwareSchema,
            expectedResult: true,
            expectedLogMessage: [
                'log.deviceReinstallingFirmwareRequiredFirmwareNotInstalled',
                'log.deviceReinstallingFirmwareInUpdatePath',
                'log.deviceReinstallingFirmwareRequiredFirmwareAlreadyInstalled',
            ],
            logVariables: [
                ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 3)], // Same as in L1 firmware after update
                ['version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 3)], // Same as in L1 firmware after update
                [
                    'version' => $deviceFirmwareVersion,
                    'requiredVersion' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                ],
            ]
        );
    }

    protected function removeCommunicationLogs()
    {
        $queryBuilder = $this->getRepository(CommunicationLog::class)->createQueryBuilder('cm');
        $queryBuilder->delete();
        $queryBuilder->getQuery()->execute();
    }

    protected function executeProcessFirmwareUpdatePathTest(DeviceType $deviceType, int $deviceId, int $firmwareId, ?string $firmwareVersion, string $feature, FirmwareVersionSchema $firmwareSchema, bool $expectedResult, array|string|null $expectedLogMessage = null, array $logVariables = []
    ) {
        $deviceCommunicationFactory = $this->getService(DeviceCommunicationFactory::class);
        $communicationLogManager = $this->getService(CommunicationLogManager::class);
        $deviceCommunication = $deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);

        $firmware = $this->getRepository(Firmware::class)->find($firmwareId);
        $this->assertNotNull($firmware);

        $device = $this->getRepository(Device::class)->find($deviceId);
        $this->assertNotNull($device);

        $this->getEntityManager()->detach($deviceType);

        $deviceTypeReloaded = $this->getRepository(DeviceType::class)->find($deviceType->getId());
        $this->assertNotNull($deviceTypeReloaded);

        $communicationLogManager->setDeviceType($deviceTypeReloaded);
        $communicationLogManager->setDevice($device);

        $deviceCommunication->setDevice($device);
        $deviceCommunication->setDeviceType($deviceTypeReloaded);
        $deviceCommunication->setResponse(new Response());

        $getAllowDowngradeFirmware = 'getAllowDowngradeFirmware'.$feature;

        $result = Accessor::invoke(
            $deviceCommunication,
            'processFirmwareUpdatePath',
            [
                Feature::from($feature),
                $firmware,
                $firmwareSchema,
                $firmwareVersion,
                $deviceTypeReloaded->$getAllowDowngradeFirmware(),
                null,
                true,
            ]
        );

        $this->assertSame($expectedResult, $result);

        $entityManagerOfCommunicationObject = Accessor::getProperty($deviceCommunication, 'entityManager');
        $entityManagerOfCommunicationObject->flush();

        if (null === $expectedLogMessage) {
            $logs = $this->getRepository(CommunicationLog::class)->findBy(['device' => $device]);
            $this->assertCount(0, $logs);

            return;
        }

        if (!is_array($expectedLogMessage)) {
            $expectedLogMessage = [$expectedLogMessage];
            $logVariables = [$logVariables];
        }

        foreach ($expectedLogMessage as $index => $logMessage) {
            $this->assertIsString($logMessage);
            $translatedMessage = $this->getTranslatedMessage(
                communicationLogManager: $communicationLogManager,
                message: $logMessage,
                messageVariables: $logVariables[$index] ?? [],
                device: $device,
                feature: Feature::from($feature),
            );

            $logs = $this->getRepository(CommunicationLog::class)->findBy(['message' => $translatedMessage]);
            $this->assertCount(1, $logs);
        }
    }

    protected function getTranslatedMessage(
        CommunicationLogManager $communicationLogManager,
        string $message,
        array $messageVariables = [],
        ?Device $device = null,
        ?Feature $feature = null,
        bool $translate = true,
        bool $processVariables = true
    ): string {
        return Accessor::invoke(
            $communicationLogManager,
            'getTranslatedMessage',
            [
                $message,
                $messageVariables,
                $device,
                $feature,
                $translate,
                $processVariables,
            ]
        );
    }
}
