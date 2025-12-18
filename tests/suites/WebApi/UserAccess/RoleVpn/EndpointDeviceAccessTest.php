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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

enum EndpointDeviceAccess: string
{
    case NONE = 'none';
    case EDIT_LIMITED = 'edit_limited'; // Description
    case EDIT_FULL = 'edit_full'; // Everything
}

enum EndpointDeviceAccessTestCase: string
{
    case GET = 'get';
    case LIST = 'list';
    case EDIT_LIMITED = 'edit_limited';
    case EDIT_FULL = 'edit_full';
    case DELETE = 'delete';
}

/**
 * Tests access level determined by access tags of VPN user to endpoint devices.
 *
 * This class is not testing access to specific endpoints based on roles (security).
 */
#[Group('full')]
class EndpointDeviceAccessTest extends AbstractTestCase
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
            TestFixtures\WebApi\UserAccess\RoleVpn\DeviceAndEndpointDeviceAccessFixtures::class,
        ];
    }

    public static function getData(): array
    {
        $data = [];

        // Data is ordered per user, then per device and then per endpoint device according to the order they are created in fixtures
        $data[] = [static::SMOKE, 'vpnat1', 'dnone-ed1none-ed2at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = [static::SMOKE, 'vpnat1', 'dnone-ed1at1-ed2at4', 'ed1at1', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat1', 'dnone-ed1at1-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed2at1', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed3at3at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dat1-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dat1-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat1', 'dat1-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dat4-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat1', 'dat4-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat1', 'dat4-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::NONE];

        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1at1-ed2at4', 'ed1at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1at1-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed3at3at4', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat3at2', 'dat1-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dat1-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dat1-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat3at2', 'dat4-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dat4-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dat4-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_LIMITED];

        $data[] = ['vpnat3', 'dnone-ed1none-ed2at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1at1-ed2at4', 'ed1at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1at1-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed3at3at4', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat3', 'dat1-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dat1-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dat1-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat3', 'dat4-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dat4-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnat3', 'dat4-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_LIMITED];

        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dnone-ed1at1-ed2at4', 'ed1at1', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat1', 'dnone-ed1at1-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed2at1', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed3at3at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dat1-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dat1-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat1', 'dat1-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dat4-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dat4-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat1', 'dat4-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::NONE];

        $data[] = [static::SMOKE, 'vpnedat3at2', 'dnone-ed1none-ed2at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dnone-ed1none-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dnone-ed1at1-ed2at4', 'ed1at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dnone-ed1at1-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dnone-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = [static::SMOKE, 'vpnedat3at2', 'dnone-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dnone-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat3at2', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed3at3at4', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat3at2', 'dat1-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dat1-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dat1-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat3at2', 'dat4-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dat4-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dat4-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_FULL];

        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1at1-ed2at4', 'ed1at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1at1-ed2at4', 'ed2at4', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at1-ed3at3at4', 'ed3at3at4', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat3', 'dat1-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dat1-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dat1-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat3', 'dat4-ed1none-ed2at1-ed3at3', 'ed1none', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dat4-ed1none-ed2at1-ed3at3', 'ed2at1', EndpointDeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dat4-ed1none-ed2at1-ed3at3', 'ed3at3', EndpointDeviceAccess::EDIT_FULL];

        $data = self::injectEndpointDeviceAccessTestCases($data);

        return $data;
    }

    public static function injectEndpointDeviceAccessTestCases(array $data): array
    {
        $injectedData = [];

        foreach ($data as $row) {
            foreach (EndpointDeviceAccessTestCase::cases() as $testCase) {
                $injectedData[] = [
                    ...$row,
                    $testCase,
                ];
            }
        }

        return $injectedData;
    }

    public static function dataProvider(): array
    {
        $data = static::getData();

        $namedData = static::getProviderNamedData($data, static::getDataName(...));

        return $namedData;
    }

    public static function getDataName($row, $key): string
    {
        $nameParts = [];

        $nameParts[] = 'Executing '.$row[4]->value.' test case';
        $nameParts[] = 'User "'.$row[0].'" against an endpoint device "'.$row[2].'" from device "'.$row[1].'"';
        $nameParts[] = 'Expected access "'.$row[3]->value.'"';

        return implode('. ', $nameParts);
    }

    #[DataProvider('dataProvider')]
    public function testEndpointDeviceAccess(string $username, string $deviceName, string $endpointDeviceName, EndpointDeviceAccess $access, EndpointDeviceAccessTestCase $testCase)
    {
        $this->loginApi($username, $username);

        $device = $this->getRepository(Device::class)->findOneBy(['name' => $deviceName]);
        $this->assertInstanceOf(Device::class, $device);

        $endpointDevice = $this->getRepository(DeviceEndpointDevice::class)->findOneBy([
            'device' => $device,
            'name' => $endpointDeviceName,
        ]);
        $this->assertInstanceOf(DeviceEndpointDevice::class, $endpointDevice);

        switch ($testCase) {
            case EndpointDeviceAccessTestCase::GET:
                $this->assertEndpointDeviceAccessGet($endpointDevice, $access);
                break;
            case EndpointDeviceAccessTestCase::LIST:
                $this->assertEndpointDeviceAccessList($endpointDevice, $access);
                break;
            case EndpointDeviceAccessTestCase::EDIT_LIMITED:
                $this->assertEndpointDeviceAccessEditLimited($endpointDevice, $access);
                break;
            case EndpointDeviceAccessTestCase::EDIT_FULL:
                $this->assertEndpointDeviceAccessEditFull($username, $endpointDevice, $access);
                break;
            case EndpointDeviceAccessTestCase::DELETE:
                $this->assertEndpointDeviceAccessDelete($endpointDevice, $access);
                break;
            default:
                throw new UnsupportedValueException($testCase);
        }
    }

    protected function assertEndpointDeviceAccessGet(DeviceEndpointDevice $endpointDevice, EndpointDeviceAccess $access): void
    {
        $this->jsonGet('/web/api/deviceendpointdevice/'.$endpointDevice->getId());

        switch ($access) {
            case EndpointDeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Endpoint device found using get endpoint', $endpointDevice, $access));
                break;
            case EndpointDeviceAccess::EDIT_LIMITED:
            case EndpointDeviceAccess::EDIT_FULL:
                $this->assertResponseIsSuccessfulJson(self::humanizeError('Endpoint device NOT found using get endpoint', $endpointDevice, $access));
                break;
            default:
                self::throwUnexpectedAccess($access);
        }
    }

    protected function assertEndpointDeviceAccessList(DeviceEndpointDevice $endpointDevice, EndpointDeviceAccess $access): void
    {
        $this->jsonPost('/web/api/deviceendpointdevice/list', [
            'page' => 1,
            'rowsPerPage' => 100,
        ]);

        $rowCount = Arr::get($this->getResponseContentAsArray(), 'rowCount');
        $this->assertIsInt($rowCount);
        if ($rowCount > 100) {
            throw new \Exception('There are more results then rowsPerPage. This may lead to invalid test results. Please adjust code.');
        }

        $results = Arr::get($this->getResponseContentAsArray(), 'results');
        $this->assertIsArray($results);

        $foundEndpointDevice = Arr::first($results, fn ($result) => $result['id'] === $endpointDevice->getId());

        switch ($access) {
            case EndpointDeviceAccess::NONE:
                $this->assertNull($foundEndpointDevice, self::humanizeError('Endpoint device found in list endpoint results', $endpointDevice, $access));
                break;
            case EndpointDeviceAccess::EDIT_LIMITED:
            case EndpointDeviceAccess::EDIT_FULL:
                $this->assertNotNull($foundEndpointDevice, self::humanizeError('Endpoint device NOT found in list endpoint results', $endpointDevice, $access));
                break;
            default:
                self::throwUnexpectedAccess($access);
        }
    }

    protected function assertEndpointDeviceAccessEditLimited(DeviceEndpointDevice $endpointDevice, EndpointDeviceAccess $access): void
    {
        $initialDescription = $endpointDevice->getDescription();

        $newDescription = 'Description assertEndpointDeviceAccessEditLimited';
        $data = [
            'description' => $newDescription,
        ];
        $this->jsonPost('/web/api/deviceendpointdevice/'.$endpointDevice->getId(), $data);

        switch ($access) {
            case EndpointDeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Endpoint device edit endpoint using limited data should return 404', $endpointDevice, $access));
                break;
            case EndpointDeviceAccess::EDIT_LIMITED:
                $this->assertResponseIsSuccessfulJson(self::humanizeError('Endpoint device edit endpoint using limited data should return 200', $endpointDevice, $access));
                // Endpoint device should be updated.
                $this->assertEndpointDeviceDescription($endpointDevice, $newDescription);
                break;
            case EndpointDeviceAccess::EDIT_FULL:
                $this->assertResponse400(self::humanizeError('Endpoint device edit endpoint using full limited should return 400', $endpointDevice, $access));
                // Endpoint device should NOT be updated.
                $this->assertEndpointDeviceDescription($endpointDevice, $initialDescription);
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    protected function assertEndpointDeviceAccessEditFull(string $username, DeviceEndpointDevice $endpointDevice, EndpointDeviceAccess $access): void
    {
        $initialName = $endpointDevice->getName();

        $user = $this->getRepository(User::class)->findOneBy(['username' => $username]);
        $this->assertInstanceOf(User::class, $user);
        $accessTagIds = $user->getAccessTags()->map(fn (AccessTag $accessTag) => $accessTag->getId())->toArray();

        $newName = 'Name assertEndpointDeviceAccessEditFull';
        $data = [
            'name' => $newName,
            'physicalIp' => $endpointDevice->getPhysicalIp(),
            'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
            'description' => $endpointDevice->getDescription(),
            'accessTags' => $accessTagIds,
        ];
        $this->jsonPost('/web/api/deviceendpointdevice/'.$endpointDevice->getId(), $data);

        switch ($access) {
            case EndpointDeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Endpoint device edit endpoint using full data should return 404', $endpointDevice, $access));
                break;
            case EndpointDeviceAccess::EDIT_LIMITED:
                $this->assertResponse400(self::humanizeError('Endpoint device edit endpoint using full data should return 400', $endpointDevice, $access));
                // Endpoint device should NOT be updated.
                $this->assertEndpointDeviceName($endpointDevice, $initialName);
                break;
            case EndpointDeviceAccess::EDIT_FULL:
                $this->assertResponseIsSuccessfulJson(self::humanizeError('Endpoint device edit endpoint using full data should return 200', $endpointDevice, $access));
                // Endpoint device should be updated.
                $this->assertEndpointDeviceName($endpointDevice, $newName);
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    protected function assertEndpointDeviceAccessDelete(DeviceEndpointDevice $endpointDevice, EndpointDeviceAccess $access): void
    {
        $this->jsonDelete('/web/api/deviceendpointdevice/'.$endpointDevice->getId());

        switch ($access) {
            case EndpointDeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Endpoint device delete endpoint should return 404', $endpointDevice, $access));
                break;
            case EndpointDeviceAccess::EDIT_LIMITED:
                $this->assertResponse403(self::humanizeError('Endpoint device delete endpoint should return 403', $endpointDevice, $access));
                break;
            case EndpointDeviceAccess::EDIT_FULL:
                $this->assertResponse204(self::humanizeError('Endpoint device delete endpoint should return 204', $endpointDevice, $access));
                // Endpoint device should be delete. Reload it.
                $endpointDevice = $this->getFreshEndpointDevice($endpointDevice);
                $this->assertNull($endpointDevice);
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    /**
     * Doctrine keeps entity data cached internally, even after loading it from database it will be merged.
     * In order to get completely fresh DeviceEndpointDevice data, entity manager needs to be cleared.
     */
    protected function getFreshEndpointDevice(DeviceEndpointDevice $endpointDevice): ?DeviceEndpointDevice
    {
        $this->getEntityManager()->clear();

        return $this->getEntityManager()->getRepository(DeviceEndpointDevice::class)->find($endpointDevice->getId());
    }

    protected function convertEndpointDeviceToComparableArray(array|DeviceEndpointDevice $endpointDevice): array
    {
        $comparableArray = [];

        if ($endpointDevice instanceof DeviceEndpointDevice) {
            $comparableArray['name'] = $endpointDevice->getName();
            $comparableArray['physicalIp'] = $endpointDevice->getPhysicalIp();
            $comparableArray['virtualIpHostPart'] = $endpointDevice->getVirtualIpHostPart();
            $comparableArray['accessTags'] = $endpointDevice->getAccessTags()->map(fn (AccessTag $accessTag) => $accessTag->getId())->toArray();
        } else {
            $comparableArray['name'] = $endpointDevice['name'] ?? null;
            $comparableArray['physicalIp'] = $endpointDevice['physicalIp'] ?? null;
            $comparableArray['virtualIpHostPart'] = $endpointDevice['virtualIpHostPart'] ?? null;
            $comparableArray['accessTags'] = $endpointDevice['accessTags'] ?? null;
        }

        return $comparableArray;
    }

    protected function assertEndpointDeviceDescription(DeviceEndpointDevice $endpointDevice, ?string $expectedDescription): void
    {
        // Always reload device before asserting
        $endpointDevice = $this->getFreshEndpointDevice($endpointDevice);
        $description = $endpointDevice->getDescription();
        $this->assertEquals($expectedDescription, $description, self::humanizeError('Endpoint device description "'.$description.'" different then expected "'.$expectedDescription.'"', $endpointDevice));
    }

    protected function assertEndpointDeviceName(DeviceEndpointDevice $endpointDevice, ?string $expectedName): void
    {
        // Always reload device before asserting
        $endpointDevice = $this->getFreshEndpointDevice($endpointDevice);
        $name = $endpointDevice->getName();
        $this->assertEquals($expectedName, $name, self::humanizeError('Endpoint device name "'.$name.'" different then expected "'.$expectedName.'"', $endpointDevice));
    }

    protected static function humanizeError(string $message, object $object, null|DeviceAccess|EndpointDeviceAccess $access = null): string
    {
        $parts = [];
        $parts[] = $message;

        if (null !== $access) {
            $parts[] = self::humanizeInvalidAccess($access);
        }

        switch (true) {
            case $object instanceof Device:
                $parts[] = self::humanizeDevice($object);
                break;
            case $object instanceof DeviceEndpointDevice:
                $parts[] = self::humanizeEndpointDevice($object);
                $parts[] = self::humanizeDevice($object->getDevice());
                break;
            default:
                throw new \Exception('Unsupported object class "'.get_class($object).'"');
        }

        return implode('. ', $parts);
    }

    protected static function humanizeDevice(Device $device): string
    {
        return 'Device "'.$device->getName().'" [ID = '.$device->getId().']';
    }

    protected static function humanizeEndpointDevice(DeviceEndpointDevice $endpointDevice): string
    {
        return 'Endpoint device "'.$endpointDevice->getName().'" [ID = '.$endpointDevice->getId().']';
    }

    protected static function humanizeInvalidAccess(DeviceAccess|EndpointDeviceAccess $access): string
    {
        return 'This is invalid behavior for "'.$access->value.'" access';
    }
}
