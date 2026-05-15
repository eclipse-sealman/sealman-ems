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
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Tests edit and visibility of access tags by VPN user on a collection of endpoint devices using device form.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class AccessTagsEndpointDeviceCollectionEditTest extends AbstractTestCase
{
    // Endpoint device access tags
    public const AT_ENDPOINT_DEVICE = 'AT_ENDPOINT_DEVICE';
    // Submitted access tags
    public const ______AT_SUBMITTED = '______AT_SUBMITTED';
    // Expected outcome
    public const ___________OUTCOME = '___________OUTCOME';
    // Expected access tags in endpoint device after outcome
    public const ________AT_OUTCOME = '________AT_OUTCOME';
    // Visible access tags in endpoint device after outcome
    public const ________AT_VISIBLE = '________AT_VISIBLE';

    public const SUCCESS = 'success';
    // One access tag is always required
    public const ERROR_400_ONEREQUIRED = 'error_400_oneRequired';
    // Access tag is invalid when it is not assigned to VPN user
    public const ERROR_400_INVALID = 'error_400_invalid';

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
            TestFixtures\WebApi\UserAccess\RoleVpn\AccessTagsEndpointDeviceCollectionFixtures::class,
        ];
    }

    /**
     * AT (Access tag) is represented by a name.
     *
     * Following access tags exists ['a', 'b', 'c', 'd', 'e']
     * User is always the same user (username = 'vpn') which has roleVpnEndpointDevices access with access tags ['b', 'c', 'd']
     * Device is always the same device (name = 'd') with the same access tags as user
     */
    public static function getData(): array
    {
        $data = [];

        $data[] = [
            [
                'ed-success0' => [
                    static::AT_ENDPOINT_DEVICE => ['a', 'b', 'd', 'e'],
                    static::______AT_SUBMITTED => ['b', 'c'],
                    static::___________OUTCOME => static::SUCCESS,
                    static::________AT_OUTCOME => ['a', 'b', 'c', 'e'],
                    static::________AT_VISIBLE => ['b', 'c'],
                ],
            ],
        ];

        $data[] = [
            [
                'ed-success0' => [
                    static::AT_ENDPOINT_DEVICE => ['a', 'c', 'e'],
                    static::______AT_SUBMITTED => ['c'],
                    static::___________OUTCOME => static::SUCCESS,
                    static::________AT_OUTCOME => ['a', 'c', 'e'],
                    static::________AT_VISIBLE => ['c'],
                ],
                'ed-success1' => [
                    static::AT_ENDPOINT_DEVICE => ['a', 'b', 'd', 'e'],
                    static::______AT_SUBMITTED => ['b'],
                    static::___________OUTCOME => static::SUCCESS,
                    static::________AT_OUTCOME => ['a', 'b', 'e'],
                    static::________AT_VISIBLE => ['b'],
                ],
            ],
        ];

        $endpointDevices = [
            'ed-success' => [
                static::AT_ENDPOINT_DEVICE => ['a', 'b'],
                static::______AT_SUBMITTED => ['b', 'c'],
                static::___________OUTCOME => static::SUCCESS,
                static::________AT_OUTCOME => ['a', 'b', 'c'],
                static::________AT_VISIBLE => ['b', 'c'],
            ],
            'ed-invalid' => [
                static::AT_ENDPOINT_DEVICE => ['b', 'c', 'e'],
                static::______AT_SUBMITTED => ['a', 'c'],
                static::___________OUTCOME => static::ERROR_400_INVALID,
                static::________AT_OUTCOME => ['b', 'c', 'e'],
                static::________AT_VISIBLE => null,
            ],
            'ed-oneRequired' => [
                static::AT_ENDPOINT_DEVICE => ['b', 'e'],
                static::______AT_SUBMITTED => [],
                static::___________OUTCOME => static::ERROR_400_ONEREQUIRED,
                static::________AT_OUTCOME => ['b', 'e'],
                static::________AT_VISIBLE => null,
            ],
        ];

        $endpointDeviceNames = array_keys($endpointDevices);

        // Generate all combinations of endpoint devices
        foreach (static::getCombinations($endpointDeviceNames) as $endpointDeviceVariations) {
            $row = [];

            foreach ($endpointDeviceVariations as $key => $endpointDeviceVariation) {
                $row[$endpointDeviceVariation.$key] = $endpointDevices[$endpointDeviceVariation];
            }

            // Whole row needs a key otherwise each endpoint device will be treated as separate parameter
            $data[] = [$row];
        }

        // 2 (predefined) + 27 (combinations) = 29 test cases
        return $data;
    }

    public static function dataProvider(): array
    {
        $data = static::getData();

        $namedData = static::getProviderNamedData($data, static::getDataName(...));

        return $namedData;
    }

    public static function getDataName($row, $key): string
    {
        $endpointDevices = $row[0];
        $nameParts = ['Number of endpoint devices = '.count($endpointDevices)];

        foreach ($endpointDevices as $name => $rowData) {
            $nameParts[] = 'Endpoint device "'.$name.'"';
            $nameParts[] = 'Expected outcome "'.$rowData[static::___________OUTCOME].'"';
            $nameParts[] = 'Endpoint device access tags ['.implode(', ', $rowData[static::AT_ENDPOINT_DEVICE]).']';
            $nameParts[] = 'Access tags submitted ['.implode(', ', $rowData[static::______AT_SUBMITTED]).']';
            $nameParts[] = 'Access tags expected ['.implode(', ', $rowData[static::________AT_OUTCOME]).']';

            if (is_array($rowData[static::________AT_VISIBLE])) {
                $nameParts[] = 'Visible access tags expected ['.implode(', ', $rowData[static::________AT_VISIBLE]).']';
            }
        }

        return implode('. ', $nameParts);
    }

    #[DataProvider('dataProvider')]
    public function testAccessTagsEndpointDeviceCollectionEdit(array $endpointDevices)
    {
        $this->setUpEndpointDevices($endpointDevices);

        $device = $this->getDevice();

        $this->loginUser();

        $endpointDevicesPayload = $this->getEndpointDevicesPayload($endpointDevices);
        $payload = [
            'endpointDevices' => $endpointDevicesPayload,
        ];

        $this->jsonPost('/web/api/device/'.$device->getId(), $payload);

        $isOutcomeSuccess = $this->isOutcomeSuccess($endpointDevices);

        if ($isOutcomeSuccess) {
            $this->assertResponseIsSuccessfulJson();
            $this->assertAccessTagsVisibility($endpointDevices);
        } else {
            $this->assertResponse400();
        }

        $this->assertOutcome($endpointDevices);
    }

    protected function setUpEndpointDevices(array $endpointDevices)
    {
        $device = $this->getDevice();
        $accessTagsByName = $this->getAccessTagsByName();
        $key = 1;

        foreach ($endpointDevices as $name => $endpointDeviceRow) {
            $endpointDevice = new DeviceEndpointDevice();
            $endpointDevice->setName($name);
            $endpointDevice->setDevice($device);
            $endpointDevice->setPhysicalIp($key.'.'.$key.'.'.$key.'.'.$key);
            $endpointDevice->setVirtualIpHostPart($key);

            $device->getEndpointDevices()->add($endpointDevice);

            foreach ($endpointDeviceRow[static::AT_ENDPOINT_DEVICE] as $accessTagName) {
                $endpointDevice->getAccessTags()->add($accessTagsByName[$accessTagName]);
            }

            $this->getEntityManager()->persist($endpointDevice);
            ++$key;
        }

        $this->getEntityManager()->flush();
    }

    protected function getEndpointDevicesPayload(array $endpointDevices): array
    {
        $payload = [];

        $accessTagsByName = $this->getAccessTagsByName();
        $endpointDevicesByName = $this->getEndpointDevicesByName();
        $key = 1;

        foreach ($endpointDevices as $name => $endpointDeviceRow) {
            $id = $endpointDevicesByName[$name]->getId();
            $payload[$id] = [
                'name' => $name,
                'physicalIp' => $key.'.'.$key.'.'.$key.'.'.$key,
                'virtualIpHostPart' => $key,
                'accessTags' => array_map(fn ($name) => $accessTagsByName[$name]->getId(), $endpointDeviceRow[static::______AT_SUBMITTED]),
            ];

            ++$key;
        }

        return $payload;
    }

    protected function isOutcomeSuccess(array $endpointDevices): bool
    {
        foreach ($endpointDevices as $endpointDevicesRow) {
            if ($endpointDevicesRow[static::___________OUTCOME] !== static::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    protected function assertOutcome(array $endpointDevices): void
    {
        $endpointDevicesByName = $this->getEndpointDevicesByName();

        foreach ($endpointDevices as $name => $endpointDevicesRow) {
            $id = $endpointDevicesByName[$name]->getId();
            $outcome = $endpointDevicesRow[static::___________OUTCOME];
            switch ($outcome) {
                case static::SUCCESS:
                    // Nothing to test
                    break;
                case static::ERROR_400_ONEREQUIRED:
                    $this->assertOneRequired('errors.children.endpointDevices.children.'.$id.'.children.accessTags.errors.0.message');
                    break;
                case static::ERROR_400_INVALID:
                    $this->assertInvalid('errors.children.endpointDevices.children.'.$id.'.children.accessTags.errors.0.message');
                    break;
                default:
                    throw new UnsupportedValueException($outcome);
            }
        }

        $isOutcomeSuccess = $this->isOutcomeSuccess($endpointDevices);
        foreach ($endpointDevices as $name => $endpointDevicesRow) {
            $accessTagsOutcome = $isOutcomeSuccess ? $endpointDevicesRow[static::________AT_OUTCOME] : $endpointDevicesRow[static::AT_ENDPOINT_DEVICE];
            $this->assertEndpointDeviceAccessTags($name, $accessTagsOutcome);
        }
    }

    protected function assertAccessTagsVisibility(array $endpointDevices): void
    {
        $accessTagsById = $this->getAccessTagsById();
        $content = $this->getResponseContentAsArray();
        $contentEndpointDevices = Arr::get($content, 'endpointDevices');

        foreach ($contentEndpointDevices as $contentEndpointDevice) {
            $name = $contentEndpointDevice['name'];
            $endpointDeviceRow = $endpointDevices[$name];

            $contentAccessTagIds = array_map(fn ($contentAccessTag) => $contentAccessTag['id'], $contentEndpointDevice['accessTags']);
            $accessTagNames = array_map(fn ($accessTagId) => $accessTagsById[$accessTagId]->getName(), $contentAccessTagIds);
            $expectedAccessTagNames = $endpointDeviceRow[static::________AT_VISIBLE];

            $this->assertEqualsCanonicalizing($expectedAccessTagNames, $accessTagNames, 'Endpoint device "'.$name.'" access tags "'.implode(', ', $accessTagNames).'" different then expected "'.implode(', ', $expectedAccessTagNames).'"');
        }
    }

    protected function assertOneRequired(string $key): void
    {
        $this->assertResponseContentAsArrayValue('validation.endpointDevice.oneAccessTagRequired', $key);
    }

    protected function assertInvalid(string $key): void
    {
        $this->assertResponseContentAsArrayValue('validation.endpointDevice.invalidAccessTag', $key);
    }

    protected function assertEndpointDeviceAccessTags(string $name, array $expectedAccessTagNames): void
    {
        // Always reload endpoint device before asserting
        $endpointDevice = $this->getFreshEndpointDevice($name);
        $existingAccessTagNames = $endpointDevice->getAccessTags()->map(fn (AccessTag $accessTag) => $accessTag->getName())->toArray();
        $this->assertSame($expectedAccessTagNames, $existingAccessTagNames, 'Endpoint device "'.$name.'" access tags "'.implode(', ', $existingAccessTagNames).'" different then expected "'.implode(', ', $expectedAccessTagNames).'"');
    }

    protected function loginUser(): void
    {
        $this->loginApi('vpn', 'vpn');
    }

    protected function getDevice(): Device
    {
        return $this->getRepository(Device::class)->findOneBy(['name' => 'd']);
    }

    protected function getEndpointDevice(string $name): ?DeviceEndpointDevice
    {
        return $this->getRepository(DeviceEndpointDevice::class)->findOneBy(['name' => $name]);
    }

    protected function getAccessTagsByName(): array
    {
        $accessTags = $this->getRepository(AccessTag::class)->findAll();
        $accessTagsByName = [];
        foreach ($accessTags as $accessTag) {
            $accessTagsByName[$accessTag->getName()] = $accessTag;
        }

        return $accessTagsByName;
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

    protected function getEndpointDevicesByName(): array
    {
        $endpointDevices = $this->getRepository(DeviceEndpointDevice::class)->findAll();
        $endpointDevicesByName = [];
        foreach ($endpointDevices as $endpointDevice) {
            $endpointDevicesByName[$endpointDevice->getName()] = $endpointDevice;
        }

        return $endpointDevicesByName;
    }

    /**
     * Doctrine keeps entity data cached internally, even after loading it from database it will be merged.
     * In order to get completely fresh DeviceEndpointDevice data, entity manager needs to be cleared.
     */
    protected function getFreshEndpointDevice(string $name): ?DeviceEndpointDevice
    {
        $this->getEntityManager()->clear();

        return $this->getEndpointDevice($name);
    }
}
