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
use App\Entity\Firmware;
use App\Enum\FirmwareVersionSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\ApiClient\ApiClientAssert;
use Tests\Utilities\WebApi\FirmwareUpdatePath\FirmwareUpdatePathTrait;

#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class FirmwareTest extends AbstractTestCase
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
    public function testFirmwareWithAnySchema(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Create firmware 1 - to be used as required firmware in firmware 2
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'Firmware 1.0.0',
                'feature' => $feature,
            ]
        );

        // This should fail due to anySchema set in device type
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'Firmware {uuid}',
                'feature' => $feature,
                'requiredFirmware' => '{firmware.id}',
            ],
            asserts: fn () => [
                ApiClientAssert::RESPONSE_400,
                ApiClientAssert::RESPONSE_JSON,
            ],
        );

        $this->assertResponse400ErrorMessage('validation.firmware.requiredFirmwareMustBeNull', 'requiredFirmware');
    }

    #[DataProvider('featuresProvider')]
    public function testFirmwareValidSchema(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);

        $deviceType->setFirmwareSchema1(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema2(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema3(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->flush();

        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Create firmware - to be used as required firmware in next firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.0',
                'feature' => $feature,
            ]
        );

        // Create firmware - to be used as required firmware in next firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.1-rc2',
                'feature' => $feature,
                'requiredFirmware' => '{firmware.id}',
            ],
        );

        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.1',
                'feature' => $feature,
                'requiredFirmware' => '{firmware.id}',
            ],
        );
    }

    #[DataProvider('featuresProvider')]
    public function testFirmwareVersionNotHigher(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);

        $deviceType->setFirmwareSchema1(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema2(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema3(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->flush();

        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Create firmware - to be used as required firmware in next firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.0',
                'feature' => $feature,
            ]
        );

        // Create firmware - to be used as required firmware in next firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.1-rc2',
                'feature' => $feature,
                'requiredFirmware' => '{firmware.id}',
            ],
        );

        // Create firmware - that is not higher than previous one
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.1-rc1',
                'feature' => $feature,
                'requiredFirmware' => '{firmware.id}',
            ],
            asserts: fn () => [
                ApiClientAssert::RESPONSE_400,
                ApiClientAssert::RESPONSE_JSON,
            ],
        );

        $this->assertResponse400ErrorMessage('validation.firmware.requiredFirmwareNotHigherVersion', 'requiredFirmware');
    }

    #[DataProvider('featuresProvider')]
    public function testFirmwareInvalidVersionSchema(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);

        $deviceType->setFirmwareSchema1(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema2(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema3(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->flush();

        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Create firmware - that has invalid version schema
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'Invalid version',
                'feature' => $feature,
            ],
            asserts: fn () => [
               ApiClientAssert::RESPONSE_400,
               ApiClientAssert::RESPONSE_JSON,
            ],
        );

        $this->assertResponse400ErrorMessage('validation.firmware.firmwareVersionNotMatchingSchema', 'version');

        // Create firmware - to be used as required firmware in next firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.0',
                'feature' => $feature,
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        // Create firmware - that has invalid version schema
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'Invalid version',
                'feature' => $feature,
                'requiredFirmware' => '{firmware.id}',
            ],
            asserts: fn () => [
               ApiClientAssert::RESPONSE_400,
               ApiClientAssert::RESPONSE_JSON,
            ],
        );

        $this->assertResponse400ErrorMessage('validation.firmware.firmwareVersionNotMatchingSchema', 'version');

        // Modify firmware - to have invalid version schema - this should never happen, but validator should catch it
        $firmware = $this->getRepository(Firmware::class)->findOneBy(['id' => $firmwareId]);

        $firmware->setVersion('Invalid version');
        $this->getEntityManager()->persist($firmware);
        $this->getEntityManager()->flush();

        // Create firmware - that has required firmware with invalid version schema
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.0',
                'feature' => $feature,
                'requiredFirmware' => $firmwareId,
            ],
            asserts: fn () => [
               ApiClientAssert::RESPONSE_400,
               ApiClientAssert::RESPONSE_JSON,
            ],
        );

        $this->assertResponse400ErrorMessage('validation.firmware.requiredFirmwareInvalid', 'requiredFirmware');
    }

    #[DataProvider('featuresProvider')]
    public function testFirmwareInvalidFeature(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);

        $deviceType->setFirmwareSchema1(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema2(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema3(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->flush();

        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $invalidFeature = $this->getOtherFeature($feature);

        // Create firmware - to be used as required firmware in next firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.0',
                'feature' => $invalidFeature,
            ]
        );

        // Create firmware - that has invalid version schema
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.1',
                'feature' => $feature,
                'requiredFirmware' => '{firmware.id}',
            ],
            asserts: fn () => [
               ApiClientAssert::RESPONSE_400,
               ApiClientAssert::RESPONSE_JSON,
            ],
        );

        $this->assertResponse400ErrorMessage('validation.firmware.requiredFirmwareInvalid', 'requiredFirmware');
    }

    #[DataProvider('featuresProvider')]
    public function testFirmwareInvalidDeviceType(string $feature)
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);

        $deviceType->setFirmwareSchema1(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->flush();

        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Create firmware - to be used as required firmware in next firmware
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.0',
            ]
        );

        $firmwareId = $this->getResponseValue('id');
        $this->assertNotNull($firmwareId);

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK600']);

        $deviceType->setFirmwareSchema1(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema2(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $deviceType->setFirmwareSchema3(FirmwareVersionSchema::V_SEMANTIC_VERSIONING);
        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->flush();

        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        // Create firmware - that has invalid version schema
        $this->getApiClient()->request(
            uri: '/web/api/firmware/create',
            parameters: [
                'version' => 'v1.0.1',
                'feature' => $feature,
                'requiredFirmware' => $firmwareId,
            ],
            asserts: fn () => [
               ApiClientAssert::RESPONSE_400,
               ApiClientAssert::RESPONSE_JSON,
            ],
        );

        $this->assertResponse400ErrorMessage('validation.firmware.requiredFirmwareInvalid', 'requiredFirmware');
    }
}
