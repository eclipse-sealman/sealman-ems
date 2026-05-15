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

enum DeviceAccess: string
{
    case NONE = 'none';
    case VIEW = 'view';
    case EDIT_LIMITED = 'edit_limited'; // Labels and description
    case EDIT_FULL = 'edit_full'; // Labels, description and endpoint devices
}

enum DeviceAccessTestCase: string
{
    case GET = 'get';
    case LIST = 'list';
    case EDIT_LIMITED = 'edit_limited';
    case EDIT_FULL = 'edit_full';
    case EDIT_EXCESSIVE = 'edit_excessive';
    case DELETE = 'delete';
}

/**
 * Tests access level determined by access tags of VPN user to devices.
 *
 * This class is not testing access to specific endpoints based on roles (security).
 */
#[Group('full')]
#[Group('WebApiRoleVpn')] // To be used in CI parallel tests
class DeviceAccessTest extends AbstractTestCase
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

        // Data is ordered per user and then per device according to the order they are created in fixtures
        $data[] = [static::SMOKE, 'vpnat1', 'dnone', DeviceAccess::NONE];
        $data[] = ['vpnat1', 'dat4', DeviceAccess::NONE];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at4', DeviceAccess::NONE];
        $data[] = [static::SMOKE, 'vpnat1', 'dat1', DeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat1', 'dat3at1', DeviceAccess::EDIT_LIMITED];
        $data[] = [static::SMOKE, 'vpnat1', 'dnone-ed1at1-ed2at4', DeviceAccess::VIEW];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnat1', 'dnone-ed1none-ed2at1-ed3at3at4', DeviceAccess::VIEW];
        $data[] = ['vpnat1', 'dat1-ed1none-ed2at1-ed3at3', DeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat1', 'dat4-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];

        $data[] = ['vpnat3at2', 'dnone', DeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dat4', DeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at4', DeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dat1', DeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dat3at1', DeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat3at2', 'dnone-ed1at1-ed2at4', DeviceAccess::NONE];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnat3at2', 'dnone-ed1none-ed2at1-ed3at3at4', DeviceAccess::VIEW];
        $data[] = ['vpnat3at2', 'dat1-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnat3at2', 'dat4-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];

        $data[] = ['vpnat3', 'dnone', DeviceAccess::NONE];
        $data[] = ['vpnat3', 'dat4', DeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at4', DeviceAccess::NONE];
        $data[] = ['vpnat3', 'dat1', DeviceAccess::NONE];
        $data[] = ['vpnat3', 'dat3at1', DeviceAccess::EDIT_LIMITED];
        $data[] = ['vpnat3', 'dnone-ed1at1-ed2at4', DeviceAccess::NONE];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnat3', 'dnone-ed1none-ed2at1-ed3at3at4', DeviceAccess::VIEW];
        $data[] = ['vpnat3', 'dat1-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnat3', 'dat4-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];

        $data[] = ['vpnedat1', 'dnone', DeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dat4', DeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at4', DeviceAccess::NONE];
        $data[] = ['vpnedat1', 'dat1', DeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat1', 'dat3at1', DeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat1', 'dnone-ed1at1-ed2at4', DeviceAccess::VIEW];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnedat1', 'dnone-ed1none-ed2at1-ed3at3at4', DeviceAccess::VIEW];
        $data[] = ['vpnedat1', 'dat1-ed1none-ed2at1-ed3at3', DeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat1', 'dat4-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];

        $data[] = [static::SMOKE, 'vpnedat3at2', 'dnone', DeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dat4', DeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dnone-ed1none-ed2at4', DeviceAccess::NONE];
        $data[] = ['vpnedat3at2', 'dat1', DeviceAccess::NONE];
        $data[] = [static::SMOKE, 'vpnedat3at2', 'dat3at1', DeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat3at2', 'dnone-ed1at1-ed2at4', DeviceAccess::NONE];
        $data[] = [static::SMOKE, 'vpnedat3at2', 'dnone-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnedat3at2', 'dnone-ed1none-ed2at1-ed3at3at4', DeviceAccess::VIEW];
        $data[] = ['vpnedat3at2', 'dat1-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnedat3at2', 'dat4-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];

        $data[] = ['vpnedat3', 'dnone', DeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dat4', DeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at4', DeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dat1', DeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dat3at1', DeviceAccess::EDIT_FULL];
        $data[] = ['vpnedat3', 'dnone-ed1at1-ed2at4', DeviceAccess::NONE];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnedat3', 'dnone-ed1none-ed2at1-ed3at3at4', DeviceAccess::VIEW];
        $data[] = ['vpnedat3', 'dat1-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];
        $data[] = ['vpnedat3', 'dat4-ed1none-ed2at1-ed3at3', DeviceAccess::VIEW];

        $data = self::injectDeviceAccessTestCases($data);

        return $data;
    }

    public static function injectDeviceAccessTestCases(array $data): array
    {
        $injectedData = [];

        foreach ($data as $row) {
            foreach (DeviceAccessTestCase::cases() as $testCase) {
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

        $nameParts[] = 'Executing '.$row[3]->value.' test case';
        $nameParts[] = 'User "'.$row[0].'" against a device "'.$row[1].'"';
        $nameParts[] = 'Expected access "'.$row[2]->value.'"';

        return implode('. ', $nameParts);
    }

    #[DataProvider('dataProvider')]
    public function testDeviceAccess(string $username, string $deviceName, DeviceAccess $access, DeviceAccessTestCase $testCase)
    {
        $this->loginApi($username, $username);

        $device = $this->getRepository(Device::class)->findOneBy(['name' => $deviceName]);
        $this->assertInstanceOf(Device::class, $device);

        switch ($testCase) {
            case DeviceAccessTestCase::GET:
                $this->assertDeviceAccessGet($device, $access);
                break;
            case DeviceAccessTestCase::LIST:
                $this->assertDeviceAccessList($device, $access);
                break;
            case DeviceAccessTestCase::EDIT_LIMITED:
                $this->assertDeviceAccessEditLimited($device, $access);
                break;
            case DeviceAccessTestCase::EDIT_FULL:
                $this->assertDeviceAccessEditFull($username, $device, $access);
                break;
            case DeviceAccessTestCase::EDIT_EXCESSIVE:
                $this->assertDeviceAccessEditExcessive($device, $access);
                break;
            case DeviceAccessTestCase::DELETE:
                $this->assertDeviceAccessDelete($device, $access);
                break;
            default:
                throw new UnsupportedValueException($testCase);
        }
    }

    protected function assertDeviceAccessGet(Device $device, DeviceAccess $access): void
    {
        $this->jsonGet('/web/api/device/'.$device->getId());

        switch ($access) {
            case DeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Device get endpoint should return 404', $device, $access));
                break;
            case DeviceAccess::EDIT_LIMITED:
            case DeviceAccess::EDIT_FULL:
            case DeviceAccess::VIEW:
                $this->assertResponseIsSuccessfulJson(self::humanizeError('Device get endpoint should return 200', $device, $access));
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    protected function assertDeviceAccessList(Device $device, DeviceAccess $access): void
    {
        $this->jsonPost('/web/api/device/list', [
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

        $foundDevice = Arr::first($results, fn ($result) => $result['id'] === $device->getId());

        switch ($access) {
            case DeviceAccess::NONE:
                $this->assertNull($foundDevice, self::humanizeError('Device found in list endpoint results', $device, $access));
                break;
            case DeviceAccess::EDIT_LIMITED:
            case DeviceAccess::EDIT_FULL:
            case DeviceAccess::VIEW:
                $this->assertNotNull($foundDevice, self::humanizeError('Device NOT found in list endpoint results', $device, $access));
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    protected function assertDeviceAccessEditLimited(Device $device, DeviceAccess $access): void
    {
        $newDescription = 'Description assertDeviceAccessEditLimited';
        $data = [
            'labels' => [],
            'description' => $newDescription,
        ];
        $this->jsonPost('/web/api/device/'.$device->getId(), $data);

        switch ($access) {
            case DeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Device edit endpoint using limited data should return 404', $device, $access));
                break;
            case DeviceAccess::VIEW:
                $this->assertResponse403(self::humanizeError('Device edit endpoint using limited data should return 403', $device, $access));
                break;
            case DeviceAccess::EDIT_LIMITED:
            case DeviceAccess::EDIT_FULL:
                $this->assertResponseIsSuccessfulJson(self::humanizeError('Device edit endpoint using limited data should return 200', $device, $access));
                $this->assertDeviceDescription($device, $newDescription);
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    protected function assertDeviceAccessEditFull(string $username, Device $device, DeviceAccess $access): void
    {
        $initialDescription = $device->getDescription();
        $initialEndpointDevices = [];
        foreach ($device->getEndpointDevices() as $endpointDevice) {
            $initialEndpointDevices[] = $this->convertEndpointDeviceToComparableArray($endpointDevice);
        }

        $user = $this->getRepository(User::class)->findOneBy(['username' => $username]);
        $this->assertInstanceOf(User::class, $user);
        $accessTagIds = $user->getAccessTags()->map(fn (AccessTag $accessTag) => $accessTag->getId())->toArray();

        // There are maximum of 3 endpoint devices in fixtures so physicalIp = 4.4.4.4 and virtualIpHostPart = 4
        $newEndpointDevice = [
            'name' => 'Name assertDeviceAccessEditFull',
            'physicalIp' => '4.4.4.4',
            'virtualIpHostPart' => 4,
            'accessTags' => $accessTagIds,
        ];
        $newEndpointDevices = ['new' => $newEndpointDevice];
        $newDescription = 'Description assertDeviceAccessEditFull';

        // We need to calculate which endpoint devices are non-accessible by user to include them as they are expected to be present after DeviceAccess::EDIT_FULL
        $editFullEndpointDevices = [];
        foreach ($device->getEndpointDevices() as $endpointDevice) {
            if (!$this->hasIntersectingAccessTag($user, $endpointDevice)) {
                $editFullEndpointDevices[] = $this->convertEndpointDeviceToComparableArray($endpointDevice);
            }
        }
        $editFullEndpointDevices[] = $newEndpointDevice;

        $data = [
            'labels' => [],
            'description' => $newDescription,
            'endpointDevices' => $newEndpointDevices,
        ];
        $this->jsonPost('/web/api/device/'.$device->getId(), $data);

        switch ($access) {
            case DeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Device edit endpoint using full data should return 404', $device, $access));
                break;
            case DeviceAccess::VIEW:
                $this->assertResponse403(self::humanizeError('Device edit endpoint using full data should return 403', $device, $access));
                break;
            case DeviceAccess::EDIT_LIMITED:
                $this->assertResponse400(self::humanizeError('Device edit endpoint using full data should return 400', $device, $access));
                $expectedEndpointDevices = $device->getEndpointDevices()->toArray();
                // Device should NOT be updated
                $this->assertDeviceDescription($device, $initialDescription);
                $this->assertDeviceEndpointDevices($device, $initialEndpointDevices);
                break;
            case DeviceAccess::EDIT_FULL:
                $this->assertResponseIsSuccessfulJson(self::humanizeError('Device edit endpoint using full data should return 200', $device, $access));
                // Device should be updated
                $this->assertDeviceDescription($device, $newDescription);
                $this->assertDeviceEndpointDevices($device, $editFullEndpointDevices);
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    protected function assertDeviceAccessEditExcessive(Device $device, DeviceAccess $access): void
    {
        $initialName = $device->getName();
        $newName = 'Name assertDeviceAccessEditExcessive';
        $data = [
            'name' => $newName,
            'labels' => [],
            'description' => 'Any description',
        ];
        $this->jsonPost('/web/api/device/'.$device->getId(), $data);

        switch ($access) {
            case DeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Device edit endpoint using excessive data should return 404', $device, $access));
                break;
            case DeviceAccess::VIEW:
                $this->assertResponse403(self::humanizeError('Device edit endpoint using excessive data should return 403', $device, $access));
                break;
            case DeviceAccess::EDIT_LIMITED:
            case DeviceAccess::EDIT_FULL:
                $this->assertResponse400(self::humanizeError('Device edit endpoint using excessive data should return 400', $device, $access));
                // Device should NOT be updated.
                $this->assertDeviceName($device, $initialName);
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    protected function assertDeviceAccessDelete(Device $device, DeviceAccess $access): void
    {
        $this->jsonDelete('/web/api/device/'.$device->getId());

        switch ($access) {
            case DeviceAccess::NONE:
                $this->assertResponse404(self::humanizeError('Device delete endpoint should return 404', $device, $access));
                break;
            case DeviceAccess::EDIT_LIMITED:
            case DeviceAccess::EDIT_FULL:
            case DeviceAccess::VIEW:
                $this->assertResponse403(self::humanizeError('Device delete endpoint should return 403', $device, $access));
                break;
            default:
                throw new UnsupportedValueException($access);
        }
    }

    /**
     * Doctrine keeps entity data cached internally, even after loading it from database it will be merged.
     * In order to get completely fresh Device data, entity manager needs to be cleared.
     */
    protected function getFreshDevice(Device $device): ?Device
    {
        $this->getEntityManager()->clear();

        return $this->getEntityManager()->getRepository(Device::class)->find($device->getId());
    }

    protected function assertDeviceName(Device $device, ?string $expectedName): void
    {
        // Always reload device before asserting
        $device = $this->getFreshDevice($device);
        $name = $device->getName();
        $this->assertEquals($expectedName, $name, self::humanizeError('Device name "'.$name.'" different then expected "'.$expectedName.'"', $device));
    }

    protected function assertDeviceDescription(Device $device, ?string $expectedDescription): void
    {
        // Always reload device before asserting
        $device = $this->getFreshDevice($device);
        $description = $device->getDescription();
        $this->assertEquals($expectedDescription, $description, self::humanizeError('Device description "'.$description.'" different then expected "'.$expectedDescription.'"', $device));
    }

    protected function assertDeviceEndpointDevices(Device $device, array $expectedEndpointDevices): void
    {
        // Always reload device before asserting
        $device = $this->getFreshDevice($device);
        $endpointDevicesCount = count($device->getEndpointDevices());
        $expectedEndpointDevicesCount = count($expectedEndpointDevices);
        $this->assertEquals($expectedEndpointDevicesCount, $endpointDevicesCount, self::humanizeError('Device endpoint device count "'.$expectedEndpointDevicesCount.'" different then expected "'.$endpointDevicesCount.'"', $device));

        foreach ($device->getEndpointDevices() as $key => $endpointDevice) {
            $expectedEndpointDevice = $expectedEndpointDevices[$key];

            $this->assertEndpointDeviceEquals($expectedEndpointDevice, $endpointDevice);
        }
    }

    protected function assertEndpointDeviceEquals(array|DeviceEndpointDevice $expectedEndpointDevice, array|DeviceEndpointDevice $endpointDevice): void
    {
        if (!$expectedEndpointDevice instanceof DeviceEndpointDevice && !$endpointDevice instanceof DeviceEndpointDevice) {
            throw new \Exception('assertEndpointDeviceEquals requires one of parameters to be '.DeviceEndpointDevice::class);
        }

        $representative = $expectedEndpointDevice instanceof DeviceEndpointDevice ? $expectedEndpointDevice : $endpointDevice;

        $expected = $this->convertEndpointDeviceToComparableArray($expectedEndpointDevice);
        $actual = $this->convertEndpointDeviceToComparableArray($endpointDevice);

        $this->assertEquals($expected, $actual, self::humanizeError('Endpoint device is different then expected', $representative));
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

    protected static function humanizeError(string $message, object $object, null|DeviceAccess $access = null): string
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
                throw new UnsupportedValueException($object);
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

    protected static function humanizeInvalidAccess(DeviceAccess $access): string
    {
        return 'This is invalid behavior for "'.$access->value.'" access';
    }

    protected function hasIntersectingAccessTag(User $user, DeviceEndpointDevice $endpointDevice): bool
    {
        foreach ($endpointDevice->getAccessTags() as $accessTag) {
            if ($user->getAccessTags()->contains($accessTag)) {
                return true;
            }
        }

        return false;
    }
}
