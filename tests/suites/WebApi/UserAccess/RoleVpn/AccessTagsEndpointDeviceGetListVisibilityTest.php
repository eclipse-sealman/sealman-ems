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

namespace Tests\Suites\WebApi\UserAccess\RoleVpn;

use App\DataFixtures as ProdFixtures;
use App\Entity\AccessTag;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\User;
use App\Exception\UnsupportedValueException;
use Carve\ApiBundle\Helper\Arr;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Tests access tags visibility by VPN user for get and list endpoints.
 *
 * Visibility of access tags for form requests is tested by:
 * - AccessTagsEndpointDeviceCollectionEditTest
 * - AccessTagsEndpointDeviceCollectionCreateTest
 * - EndpointDeviceAccessTagsTest.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class AccessTagsEndpointDeviceGetListVisibilityTest extends AbstractTestCase
{
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
            TestFixtures\WebApi\UserAccess\RoleVpn\AccessTagsEndpointDeviceGetListVisibilityFixtures::class,
        ];
    }

    public function testEndpointDeviceList()
    {
        $this->loginUser();

        $payload = [
            'page' => 1,
            'rowsPerPage' => 100,
        ];

        $this->jsonPost('/web/api/deviceendpointdevice/list', $payload);
        $this->assertResponseIsSuccessfulJson();

        $rowCount = Arr::get($this->getResponseContentAsArray(), 'rowCount');
        $this->assertIsInt($rowCount);
        if ($rowCount > 100) {
            throw new \Exception('There are more results then rowsPerPage. This may lead to invalid test results. Please adjust code.');
        }

        $endpointDevices = Arr::get($this->getResponseContentAsArray(), 'results');

        foreach ($endpointDevices as $endpointDevice) {
            $name = Arr::get($endpointDevice, 'name');
            $accessTags = Arr::get($endpointDevice, 'accessTags');
            $accessTagIds = array_map(fn ($accessTag) => $accessTag['id'], $accessTags);

            $this->assertAccessTagsVisibility($name, $accessTagIds);
        }
    }

    public function testEndpointDeviceGet()
    {
        $this->loginUser();

        $endpointDevices = $this->getRepository(DeviceEndpointDevice::class)->findAll();

        foreach ($endpointDevices as $endpointDevice) {
            $this->jsonGet('/web/api/deviceendpointdevice/'.$endpointDevice->getId());
            $this->assertResponseIsSuccessfulJson();

            $content = $this->getResponseContentAsArray();
            $name = Arr::get($content, 'name');
            $accessTags = Arr::get($content, 'accessTags');
            $accessTagIds = array_map(fn ($accessTag) => $accessTag['id'], $accessTags);

            $this->assertAccessTagsVisibility($name, $accessTagIds);
        }
    }

    /**
     * Following access tags exists ['a', 'b', 'c', 'd', 'e']
     * User is always the same user (username = 'vpn') which has roleVpnEndpointDevices access with access tags ['b', 'c', 'd']
     * Device has no access tags.
     *
     * Cases are ordered according to the order they are created in fixtures
     */
    protected function assertAccessTagsVisibility(string $name, array $accessTagIds): void
    {
        $expectedAccessTagNames = [];

        switch ($name) {
            case 'd1:ed-c':
                $expectedAccessTagNames = ['c'];
                break;
            case 'd2:ed-abc':
                $expectedAccessTagNames = ['b', 'c'];
                break;
            case 'd3:ed-abcde':
                $expectedAccessTagNames = ['b', 'c', 'd'];
                break;
            case 'd4:ed-de':
                $expectedAccessTagNames = ['d'];
                break;
            case 'd5:ed-bc':
                $expectedAccessTagNames = ['b', 'c'];
                break;
            case 'd5:ed-de':
                $expectedAccessTagNames = ['d'];
                break;
            case 'd6:ed-abc':
                $expectedAccessTagNames = ['b', 'c'];
                break;
            case 'd6:ed-cde':
                $expectedAccessTagNames = ['c', 'd'];
                break;
            case 'd6:ed-abcde':
                $expectedAccessTagNames = ['b', 'c', 'd'];
                break;
            default:
                throw new UnsupportedValueException($name);
        }

        $accessTagsById = $this->getAccessTagsById();
        $accessTagNames = array_map(fn ($accessTagId) => $accessTagsById[$accessTagId]->getName(), $accessTagIds);

        $this->assertSame($expectedAccessTagNames, $accessTagNames, 'Endpoint device "'.$name.'" access tags "'.implode(', ', $accessTagNames).'" different then expected "'.implode(', ', $expectedAccessTagNames).'"');
    }

    protected function loginUser(): void
    {
        $this->loginApi('vpn', 'vpn');
    }

    protected function getEndpointDevice(string $name): DeviceEndpointDevice
    {
        return $this->getRepository(DeviceEndpointDevice::class)->findOneBy(['name' => $name]);
    }

    protected function getAccessTagsById(): array
    {
        $accessTags = $this->getRepository(AccessTag::class)->findAll();
        $accessTagsById = [];
        foreach ($accessTags as $accessTag) {
            $accessTagsById[$accessTag->getId()] = $accessTag;
        }

        return $accessTagsById;
    }
}
