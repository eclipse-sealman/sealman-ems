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
class AuditLogEndpointDeviceAccessTagsTest extends AbstractTestCase
{
    use AssertAuditLogTrait;

    // Endpoint device name
    public const ENDPOINT_DEVICE = 'ENDPOINT_DEVICE';
    // Endpoint device old access tags
    public const _________AT_OLD = '_________AT_OLD';
    // Endpoint device submitted access tags
    public const ___AT_SUBMITTED = '___AT_SUBMITTED';
    // Endpoint device new access tags
    public const _________AT_NEW = '_________AT_NEW';

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
     * AT (Access tag) is represented by a name.
     *
     * Following access tags exists ['a', 'b', 'c', 'd', 'e']
     * User is always the same user (username = 'vpn_ed:acd') which has roleVpnEndpointDevices access with access tags ['a', 'c', 'd']
     * Device is always the same device (name = 'd:abcde') with all access tags
     *
     * All endpoint devices are assigned to the same device.
     *
     * Used by AuditLogEndpointDeviceCollectionAccessTagsTest.
     */
    public static function getData(): array
    {
        $data = [];

        $reusable = [
            static::ENDPOINT_DEVICE => 'ed1:c',
            static::_________AT_OLD => ['c'],
        ];

        $cases = [
            [
                static::___AT_SUBMITTED => ['a', 'c'],
                static::_________AT_NEW => ['a', 'c'],
            ],
            [
                static::___AT_SUBMITTED => ['a', 'c', 'd'],
                static::_________AT_NEW => ['a', 'c', 'd'],
            ],
            [
                static::___AT_SUBMITTED => ['a', 'd'],
                static::_________AT_NEW => ['a', 'd'],
            ],
            [
                static::___AT_SUBMITTED => ['d'],
                static::_________AT_NEW => ['d'],
            ],
        ];

        $data = array_merge($data, static::getDataCases($reusable, $cases));

        $reusable = [
            static::ENDPOINT_DEVICE => 'ed2:abcde',
            static::_________AT_OLD => ['a', 'b', 'c', 'd', 'e'],
        ];

        $cases = [
            [
                static::___AT_SUBMITTED => ['a', 'c'],
                static::_________AT_NEW => ['a', 'b', 'c', 'e'],
            ],
            [
                static::___AT_SUBMITTED => ['a', 'd'],
                static::_________AT_NEW => ['a', 'b', 'd', 'e'],
            ],
            [
                static::___AT_SUBMITTED => ['d'],
                static::_________AT_NEW => ['b', 'd', 'e'],
            ],
            [
                // AuditLog should not be generated (no change)
                static::___AT_SUBMITTED => ['a', 'c', 'd'],
                static::_________AT_NEW => null,
            ],
        ];

        $data = array_merge($data, static::getDataCases($reusable, $cases));

        // 8 test cases
        return $data;
    }

    public static function getDataCases(array $reusable, array $cases): array
    {
        $data = [];

        foreach ($cases as $case) {
            $data[] = [
                ...$reusable,
                ...$case,
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
        $nameParts = [];

        $nameParts[] = 'Endpoint device "'.$row[static::ENDPOINT_DEVICE].'"';
        $nameParts[] = 'Endpoint device access tags ['.implode(', ', $row[static::_________AT_OLD]).']';
        $nameParts[] = 'Access tags submitted ['.implode(', ', $row[static::___AT_SUBMITTED]).']';

        if (null === $row[static::_________AT_NEW]) {
            $nameParts[] = 'No change expected';
        } else {
            $nameParts[] = 'Access tags expected ['.implode(', ', $row[static::_________AT_NEW]).']';
        }

        return implode('. ', $nameParts);
    }

    #[DataProvider('dataProvider')]
    public function testEndpointDeviceEdit(string $name, array $old, array $submitted, ?array $new)
    {
        $this->loginUser();

        $endpointDevice = $this->getEndpointDevice($name);
        $id = $endpointDevice->getId();

        $this->jsonPost('/web/api/deviceendpointdevice/'.$id, [
            'name' => $endpointDevice->getName(),
            'physicalIp' => $endpointDevice->getPhysicalIp(),
            'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
            'accessTags' => $this->getAccessTagsIds($submitted),
        ]);
        $this->assertResponseIsSuccessfulJson();

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
            $this->assertEntityNull($change);
        }
    }

    protected function loginUser(): void
    {
        $this->loginApi('vpn_ed:acd', 'vpn_ed:acd');
    }

    protected function getEndpointDevice(string $name): DeviceEndpointDevice
    {
        return $this->getRepository(DeviceEndpointDevice::class)->findOneBy(['name' => $name]);
    }

    protected function getAccessTagsIds(array $names): array
    {
        $accessTags = $this->getRepository(AccessTag::class)->findBy(['name' => $names]);
        $accessTagIds = array_map(fn ($accessTag) => $accessTag->getId(), $accessTags);

        return $accessTagIds;
    }
}
