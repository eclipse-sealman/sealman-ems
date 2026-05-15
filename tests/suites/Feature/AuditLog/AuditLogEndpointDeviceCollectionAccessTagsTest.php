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

namespace Tests\Suites\Feature\AuditLog;

use App\DataFixtures as ProdFixtures;
use App\Entity\AccessTag;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\User;
use App\Enum\AuditLogChangeType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Feature\AuditLog\AssertAuditLogTrait;

/**
 * This test suite aims to test editing access tags in endpoint devices by roleVpn user.
 *
 * Editing of access tags is a custom solution as it restores not owned access tags after edit.
 */
#[Group('full')]
#[Group('Feature')] // To be used in CI parallel tests
class AuditLogEndpointDeviceCollectionAccessTagsTest extends AbstractTestCase
{
    use AssertAuditLogTrait;

    // Endpoint devices
    public const ENDPOINT_DEVICES = 'ENDPOINT_DEVICES';

    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
            ProdFixtures\UserDeviceSecretCredentialsFixtures::class,
            ProdFixtures\UserDeviceX509CredentialsFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\Configuration\ScepFixtures::class,
            TestFixtures\Configuration\VpnFixtures::class,
            TestFixtures\AuditLog\AuditLogAccessTagsFixtures::class,
        ];
    }

    /**
     * This data is a combinations of arrays from AuditLogEndpointDeviceAccessTagsTest::getData().
     */
    public static function getData(): array
    {
        $flatData = AuditLogEndpointDeviceAccessTagsTest::getData();

        $groupedData = [];
        foreach ($flatData as $flatRow) {
            $groupedData[$flatRow[AuditLogEndpointDeviceAccessTagsTest::ENDPOINT_DEVICE]] ??= [];
            $groupedData[$flatRow[AuditLogEndpointDeviceAccessTagsTest::ENDPOINT_DEVICE]][] = $flatRow;
        }

        $cases = static::getArrayCombinations(array_values($groupedData));

        $data = [];

        foreach ($cases as $case) {
            $data[] = [
                static::ENDPOINT_DEVICES => $case,
            ];
        }

        return $data;
    }

    public static function dataProvider(): array
    {
        $data = static::getData();

        $namedData = static::getProviderNamedData($data, static::getDataName(...));

        $namedData = static::removeDataRowKeys($namedData);

        return $namedData;
    }

    public static function getDataName($row, $key): string
    {
        $nameParts = ['Number of endpoint devices = '.count($row[static::ENDPOINT_DEVICES])];

        foreach ($row[static::ENDPOINT_DEVICES] as $name => $rowData) {
            $nameParts[] = 'Endpoint device "'.$rowData[AuditLogEndpointDeviceAccessTagsTest::ENDPOINT_DEVICE].'"';
            $nameParts[] = 'Endpoint device access tags ['.implode(', ', $rowData[AuditLogEndpointDeviceAccessTagsTest::_________AT_OLD]).']';
            $nameParts[] = 'Access tags submitted ['.implode(', ', $rowData[AuditLogEndpointDeviceAccessTagsTest::___AT_SUBMITTED]).']';

            if (null === $rowData[AuditLogEndpointDeviceAccessTagsTest::_________AT_NEW]) {
                $nameParts[] = 'No change expected';
            } else {
                $nameParts[] = 'Access tags expected ['.implode(', ', $rowData[AuditLogEndpointDeviceAccessTagsTest::_________AT_NEW]).']';
            }
        }

        return implode('. ', $nameParts);
    }

    #[DataProvider('dataProvider')]
    public function testEndpointDeviceCollection(array $endpointDeviceRows)
    {
        $this->loginUser();

        $deviceId = $this->getDevice()->getId();
        $endpointDevicesPayload = [];

        foreach ($endpointDeviceRows as $endpointDeviceRow) {
            $name = $endpointDeviceRow[AuditLogEndpointDeviceAccessTagsTest::ENDPOINT_DEVICE];
            $submitted = $endpointDeviceRow[AuditLogEndpointDeviceAccessTagsTest::___AT_SUBMITTED];

            $endpointDevice = $this->getEndpointDevice($name);
            $endpointDevicesPayload[$endpointDevice->getId()] = $this->getEndpointDevicePayload($endpointDevice, [
                'accessTags' => $this->getAccessTagsIds($submitted),
            ]);
        }

        $this->jsonPost('/web/api/device/'.$deviceId, [
            'endpointDevices' => $endpointDevicesPayload,
        ]);
        $this->assertResponseIsSuccessfulJson();

        foreach ($endpointDeviceRows as $endpointDeviceRow) {
            $name = $endpointDeviceRow[AuditLogEndpointDeviceAccessTagsTest::ENDPOINT_DEVICE];
            $old = $endpointDeviceRow[AuditLogEndpointDeviceAccessTagsTest::_________AT_OLD];
            $new = $endpointDeviceRow[AuditLogEndpointDeviceAccessTagsTest::_________AT_NEW];

            $endpointDevice = $this->getEndpointDevice($name);
            $id = $endpointDevice->getId();

            if (null !== $new) {
                $change = $this->findChange(DeviceEndpointDevice::class, $id);
                $this->assertChange($change, AuditLogChangeType::UPDATE, [
                    'id' => $id,
                    'accessTags' => $this->getAccessTagsIds($old),
                ], [
                    'id' => $id,
                    'accessTags' => $this->getAccessTagsIds($new),
                ]);
            } else {
                $change = $this->getLastChange(DeviceEndpointDevice::class, [
                    'entityId' => $id,
                    'type' => AuditLogChangeType::UPDATE,
                ]);
                $this->assertNull($change);
            }
        }
    }

    protected function loginUser(): void
    {
        $this->loginApi('vpn_ed:acd', 'vpn_ed:acd');
    }

    protected function getDevice(): Device
    {
        return $this->getRepository(Device::class)->findOneBy([]);
    }

    protected function getEndpointDevice(string $name): DeviceEndpointDevice
    {
        return $this->getRepository(DeviceEndpointDevice::class)->findOneBy(['name' => $name]);
    }

    protected function getEndpointDevicePayload(DeviceEndpointDevice $endpointDevice, array $payload = []): array
    {
        return [
            'name' => $endpointDevice->getName(),
            'physicalIp' => $endpointDevice->getPhysicalIp(),
            'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
            'description' => $endpointDevice->getDescription(),
            ...$payload,
        ];
    }

    protected function getAccessTagsIds(array $names): array
    {
        $accessTags = $this->getRepository(AccessTag::class)->findBy(['name' => $names]);
        $accessTagIds = array_map(fn ($accessTag) => $accessTag->getId(), $accessTags);

        return $accessTagIds;
    }
}
