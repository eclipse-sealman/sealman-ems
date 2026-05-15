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
use App\Entity\DeviceType;
use App\Enum\FirmwareVersionSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\ApiClient\ApiClientAssert;
use Tests\Utilities\WebApi\FirmwareUpdatePath\FirmwareUpdatePathTrait;

#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class DeviceTypeTest extends AbstractTestCase
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

    #[DataProvider('featuresProvider')]
    public function testDeviceTypeSchemaNoFirmwares(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        foreach (FirmwareVersionSchema::cases() as $firmwareSchema) {
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
        }
    }

    #[DataProvider('featuresProvider')]
    public function testDeviceTypeSchemaValidFirmware(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        foreach (FirmwareVersionSchema::cases() as $firmwareSchema) {
            // Create firmware with valid version
            $this->getApiClient()->request(
                uri: '/web/api/firmware/create',
                parameters: [
                    'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                    'feature' => $feature,
                ]
            );
            $firmwareId = $this->getResponseValue('id');
            $this->assertNotNull($firmwareId);

            // Edit device type and set schema
            $this->getApiClient()->request(
                uri: '/web/api/devicetype/limitededit/{id}',
                method: 'POST',
                uriParameters: [
                    'id' => $deviceType->getId(),
                ],
                parameters: array_merge($this->getDeviceTypeLimitedEditValues($deviceType), [
                    'firmwareSchema'.$feature => $firmwareSchema->value,
                ])
            );

            // Edit device type and set any schema to allow for next firware creation
            $this->getApiClient()->request(
                uri: '/web/api/devicetype/limitededit/{id}',
                method: 'POST',
                uriParameters: [
                    'id' => $deviceType->getId(),
                ],
                parameters: array_merge($this->getDeviceTypeLimitedEditValues($deviceType), [
                    'firmwareSchema'.$feature => FirmwareVersionSchema::ANY_SCHEMA->value,
                ]),
            );

            $this->getApiClient()->request(
                uri: '/web/api/firmware/{id}',
                method: 'DELETE',
                uriParameters: [
                    'id' => $firmwareId,
                ]
            );

            $this->getEntityManager()->flush();
        }
    }

    #[DataProvider('featuresProvider')]
    public function testDeviceTypeSchemaInvalidFirmware(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        foreach (FirmwareVersionSchema::cases() as $firmwareSchema) {
            if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
                // Skip any schema - as any version is valid
                continue;
            }

            // Create firmware with valid version
            $this->getApiClient()->request(
                uri: '/web/api/firmware/create',
                parameters: [
                    'version' => 'abcd',
                    'feature' => $feature,
                    'deviceType' => $deviceType->getId(),
                ]
            );

            $firmwareId = $this->getResponseValue('id');
            $this->assertNotNull($firmwareId);

            // Edit device type and set schema
            $this->getApiClient()->request(
                uri: '/web/api/devicetype/limitededit/{id}',
                method: 'POST',
                uriParameters: [
                    'id' => $deviceType->getId(),
                ],
                parameters: array_merge($this->getDeviceTypeLimitedEditValues($deviceType), [
                    'firmwareSchema'.$feature => $firmwareSchema->value,
                ]),
                asserts: fn () => [
                    ApiClientAssert::RESPONSE_400,
                    ApiClientAssert::RESPONSE_JSON,
                ],
            );

            $this->assertResponse400ErrorMessage('validation.deviceType.firmwareVersionNotMatchingSchema', 'firmwareSchema'.$feature);

            $this->getApiClient()->request(
                uri: '/web/api/firmware/{id}',
                method: 'DELETE',
                uriParameters: [
                    'id' => $firmwareId,
                ]
            );

            $this->getEntityManager()->flush();
        }
    }

    #[DataProvider('featuresProvider')]
    public function testDeviceTypeAnySchemaWithRequiredFirmware(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        foreach (FirmwareVersionSchema::cases() as $firmwareSchema) {
            if (FirmwareVersionSchema::ANY_SCHEMA === $firmwareSchema) {
                // Skip any schema - as any version is valid
                continue;
            }

            // Edit device type and set schema
            $this->getApiClient()->request(
                uri: '/web/api/devicetype/{id}',
                method: 'POST',
                uriParameters: [
                    'id' => $deviceType->getId(),
                ],
                parameters: array_merge($this->getDeviceTypeEditValues($deviceType), [
                    'firmwareSchema'.$feature => $firmwareSchema->value,
                ]),
            );

            // Create firmware with valid version
            $this->getApiClient()->request(
                uri: '/web/api/firmware/create',
                parameters: [
                    'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 1),
                    'feature' => $feature,
                    'deviceType' => $deviceType->getId(),
                ]
            );

            $firmwareId = $this->getResponseValue('id');
            $this->assertNotNull($firmwareId);

            // Create firmware with valid version
            $this->getApiClient()->request(
                uri: '/web/api/firmware/create',
                parameters: [
                    'version' => $this->getValidFirmwareVersionByLevel($firmwareSchema, 2),
                    'feature' => $feature,
                    'deviceType' => $deviceType->getId(),
                    'requiredFirmware' => $firmwareId,
                ]
            );

            $newFirmwareId = $this->getResponseValue('id');
            $this->assertNotNull($newFirmwareId);

            // Edit device type and set any schema, when required firmware is set - should return 400
            $this->getApiClient()->request(
                uri: '/web/api/devicetype/limitededit/{id}',
                method: 'POST',
                uriParameters: [
                    'id' => $deviceType->getId(),
                ],
                parameters: array_merge($this->getDeviceTypeLimitedEditValues($deviceType), [
                    'firmwareSchema'.$feature => FirmwareVersionSchema::ANY_SCHEMA->value,
                ]),
                asserts: fn () => [
                    ApiClientAssert::RESPONSE_400,
                    ApiClientAssert::RESPONSE_JSON,
                ],
            );

            $this->assertResponse400ErrorMessage('validation.deviceType.firmwareSchemaAnyCannotHaveUpdatePath', 'firmwareSchema'.$feature);

            $this->getApiClient()->request(
                uri: '/web/api/firmware/{id}',
                method: 'DELETE',
                uriParameters: [
                    'id' => $newFirmwareId,
                ]
            );

            $this->getApiClient()->request(
                uri: '/web/api/firmware/{id}',
                method: 'DELETE',
                uriParameters: [
                    'id' => $firmwareId,
                ]
            );

            $this->getEntityManager()->flush();
        }
    }
}
