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

namespace Tests\Suites\WebApi\UserAccess\RoleVpn\DeviceEndpointDeviceCollection;

use App\DataFixtures as ProdFixtures;
use App\Entity\AccessTag;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\WebApi\UserAccess\AccessibleEndpointDevice;
use Tests\Utilities\WebApi\UserAccess\Action;
use Tests\Utilities\WebApi\UserAccess\NonAccessibleEndpointDevice;
use Tests\Utilities\WebApi\UserAccess\UniquenessTestCase;

/**
 * This class tests validation of uniqueness of name, physicalIp and virtualIpHostPart in endpoint device collection.
 * It is expected for them to be unique including non-accessible endpoint devices.
 *
 * Detailed description in tests\WebApi\UserAccess\RoleVpn\DeviceEndpointDeviceCollection\ProcessingTest.
 *
 * Following setup is used for each element in collection:
 * - Element 1: NonAccessibleEndpointDevice with OMITTED action
 * - Element 2: AccessibleEndpointDevice with CREATE or UPDATE action
 * - Element 3: NonAccessibleEndpointDevice with OMITTED action
 * - Element 4: AccessibleEndpointDevice with CREATE or UPDATE action
 * - Element 5: NonAccessibleEndpointDevice with OMITTED action
 *
 * For each test case a device is created with relevant endpoint devices.
 *
 * CREATE or UPDATE action will be tested with fields name, physicalIp and virtualIpHostPart using following test case:
 * - Field is unique
 * - Field is the same as one of other elements (4 test cases)
 */
#[Group('full')]
#[Group('smoke')]
class UniquenessTest extends AbstractTestCase
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
            TestFixtures\WebApi\UserAccess\RoleVpn\DeviceEndpointDeviceCollection\UniquenessTestFixtures::class,
        ];
    }

    public static function removeAuditLogsAfterFixtures(): bool
    {
        return true;
    }

    public static function getTestCases(): array
    {
        $elements1 = [
            new NonAccessibleEndpointDevice(1, Action::OMITTED),
        ];
        $elements2 = [
            new AccessibleEndpointDevice(2, Action::UNCHANGED),
            new AccessibleEndpointDevice(2, Action::CREATE),
            new AccessibleEndpointDevice(2, Action::UPDATE),
        ];
        $elements3 = [
            new NonAccessibleEndpointDevice(3, Action::OMITTED),
        ];
        $elements4 = [
            new AccessibleEndpointDevice(4, Action::UNCHANGED),
            new AccessibleEndpointDevice(4, Action::CREATE),
            new AccessibleEndpointDevice(4, Action::UPDATE),
        ];
        $elements5 = [
            new NonAccessibleEndpointDevice(5, Action::OMITTED),
        ];

        $cases = [];

        foreach (static::getArrayCombinations([$elements1, $elements2, $elements3, $elements4, $elements5]) as $key => $value) {
            $cases[] = new UniquenessTestCase('device'.$key, $value[0], $value[1], $value[2], $value[3], $value[4]);
        }

        return $cases;
    }

    public static function dataProvider(): array
    {
        $data = array_map(fn ($testCase) => [$testCase], static::getTestCases());

        $namedData = static::getProviderNamedData($data, static::getDataName(...));

        return $namedData;
    }

    public static function getDataName($row, $key): string
    {
        $testCase = $row[0];

        $nameParts = [];
        $nameParts[] = 'Data #'.$key;
        $nameParts[] = 'Uniqueness test case';

        foreach ($testCase->getElements() as $key => $element) {
            $nameParts[] = 'Element '.$key.' = '.UniquenessTestCase::getDataElementName($element);
        }

        return implode('. ', $nameParts);
    }

    #[DataProvider('dataProvider')]
    public function testNameUniqueness(UniquenessTestCase $testCase)
    {
        $this->assertFieldUniqueness('name', $testCase);
    }

    #[DataProvider('dataProvider')]
    public function testPhysicalIpUniqueness(UniquenessTestCase $testCase)
    {
        $this->assertFieldUniqueness('physicalIp', $testCase);
    }

    #[DataProvider('dataProvider')]
    public function testVirtualIpHostPartUniqueness(UniquenessTestCase $testCase)
    {
        $this->assertFieldUniqueness('virtualIpHostPart', $testCase);
    }

    public function assertFieldUniqueness(string $field, UniquenessTestCase $testCase)
    {
        $this->loginApi('vpn', 'vpn');

        $element2 = $testCase->element2;
        // Run all tests that should fail for element2
        if (Action::CREATE === $element2->action || Action::UPDATE === $element2->action) {
            $this->assertFieldUniquenessAgainst($field, $testCase, 2, 1);
            $this->assertFieldUniquenessAgainst($field, $testCase, 2, 3);
            $this->assertFieldUniquenessAgainst($field, $testCase, 2, 4);
            $this->assertFieldUniquenessAgainst($field, $testCase, 2, 5);
        }

        $element4 = $testCase->element4;
        // Run all tests that should fail for element4
        if (Action::CREATE === $element4->action || Action::UPDATE === $element4->action) {
            $this->assertFieldUniquenessAgainst($field, $testCase, 4, 1);
            $this->assertFieldUniquenessAgainst($field, $testCase, 4, 2);
            $this->assertFieldUniquenessAgainst($field, $testCase, 4, 3);
            $this->assertFieldUniquenessAgainst($field, $testCase, 4, 5);
        }

        // Run test that should succeed
        $device = $this->getDevice($testCase);
        $payload = $this->getPayload($testCase);
        $this->jsonPost('/web/api/device/'.$device->getId(), $payload);
        $this->assertResponseIsSuccessfulJson();
    }

    /**
     * Test field (name, physicalIp, virtualIpHostPart) uniqueness for $elementKey (one we want to trigger uniqueness error) against $againstKey (one which should be our unique source).
     *
     * Example:
     * $field = 'name', $elementKey = 2, $againstKey = 1 means following payload:
     * ED 1 name = 'Endpoint device #1'
     * ED 2 name = 'Endpoint device #1' (non-unique against ED 1)
     */
    protected function assertFieldUniquenessAgainst(string $field, UniquenessTestCase $testCase, int $elementKey, int $againstKey)
    {
        $supportedFields = ['name', 'physicalIp', 'virtualIpHostPart'];
        if (!in_array($field, $supportedFields)) {
            throw new \InvalidArgumentException('Unsupported field "'.$field.'"');
        }

        $element = $testCase->getElements()[$elementKey];
        $againstElement = $testCase->getElements()[$againstKey];

        $originalGetter = 'getElementOriginal'.ucfirst($field);
        $updatedGetter = 'getElementUpdated'.ucfirst($field);

        // Non-unique value will be original value OR updated value when element against we are testing is being updated
        $nonUniqueValue = UniquenessTestCase::$originalGetter($againstKey);
        if (Action::UPDATE === $againstElement->action) {
            $nonUniqueValue = UniquenessTestCase::$updatedGetter($againstKey);
        }

        // Modify payload by adding non-unique field to element
        $payload = $this->getPayload($testCase, $elementKey, [
            $field => $nonUniqueValue,
        ]);

        $device = $this->getDevice($testCase);
        $this->jsonPost('/web/api/device/'.$device->getId(), $payload);

        $this->assertResponse400();

        $id = Action::CREATE === $element->action ? 'new_'.$elementKey : $this->getEndpointDevice($device, $element)->getId();
        // Expect error message in errors array
        $message = 'validation.endpointDevice.'.$field.'NotUnique';
        // Error message should be under following key
        $key = 'errors.children.endpointDevices.children.'.$id.'.children.'.$field.'.errors.0.message';

        $this->assertResponseContentAsArrayValue($message, $key, 'Expecting non-unique '.$field.' error for element '.$elementKey.' against element '.$againstKey);
    }

    protected function getPayload(UniquenessTestCase $testCase, ?int $payloadOverrideKey = null, array $payloadOverride = []): array
    {
        $device = $this->getDevice($testCase);
        $payload = [
            'endpointDevices' => [],
        ];

        foreach ($testCase->getElements() as $key => $element) {
            if ($element instanceof NonAccessibleEndpointDevice) {
                if (Action::OMITTED !== $element->action) {
                    throw new \LogicException('Unsupported action');
                }

                // Always omitted
                continue;
            }

            if ($element instanceof AccessibleEndpointDevice) {
                $endpointDevicePayload = $this->getAccessibleEndpointDevicePayload($device, $element);

                // Override payload for specific key
                if ($payloadOverrideKey === $key) {
                    $payloadKey = array_key_first($endpointDevicePayload);
                    $endpointDevicePayload = [
                        $payloadKey => [
                            ...$endpointDevicePayload[$payloadKey],
                            ...$payloadOverride,
                        ],
                    ];
                }

                // We need to preserve keys returned by function
                $payload['endpointDevices'] = $payload['endpointDevices'] + $endpointDevicePayload;
                continue;
            }

            throw new \LogicException('Unsupported element');
        }

        return $payload;
    }

    /**
     * Returns indexed array with endpoint device payload [ID => payload].
     */
    protected function getAccessibleEndpointDevicePayload(Device $device, AccessibleEndpointDevice $element): array
    {
        $key = $element->key;
        $payload = [];

        switch ($element->action) {
            case Action::UNCHANGED:
                $endpointDevice = $this->getEndpointDevice($device, $element);
                $payload[$endpointDevice->getId()] = [
                    'name' => $endpointDevice->getName(),
                    'physicalIp' => $endpointDevice->getPhysicalIp(),
                    'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
                    'accessTags' => $endpointDevice->getAccessTags()->map(fn (AccessTag $accessTag) => $accessTag->getId())->toArray(),
                ];
                break;
            case Action::CREATE:
                $payload['new_'.$key] = [
                    'name' => UniquenessTestCase::getElementOriginalName($key),
                    'physicalIp' => UniquenessTestCase::getElementOriginalPhysicalIp($key),
                    'virtualIpHostPart' => UniquenessTestCase::getElementOriginalVirtualIpHostPart($key),
                    'accessTags' => [$this->getVpnAccessTag()->getId()],
                ];
                break;
            case Action::UPDATE:
                $endpointDevice = $this->getEndpointDevice($device, $element);
                $payload[$endpointDevice->getId()] = [
                    'name' => UniquenessTestCase::getElementUpdatedName($key),
                    'physicalIp' => UniquenessTestCase::getElementUpdatedPhysicalIp($key),
                    'virtualIpHostPart' => UniquenessTestCase::getElementUpdatedVirtualIpHostPart($key),
                    'accessTags' => [$this->getVpnAccessTag()->getId()],
                ];
                break;
            default:
                static::throwUnsupportedEnum($element->action);
        }

        return $payload;
    }

    protected function getDevice(UniquenessTestCase $testCase): Device
    {
        $device = $this->getRepository(Device::class)->findOneBy(['name' => $testCase->deviceName]);
        $this->assertInstanceOf(Device::class, $device);

        return $device;
    }

    protected function getVpnAccessTag(): AccessTag
    {
        $accessTag = $this->getRepository(AccessTag::class)->findOneBy(['name' => 'vpn-access']);
        $this->assertInstanceOf(AccessTag::class, $accessTag);

        return $accessTag;
    }

    protected function getEndpointDevice(Device $device, NonAccessibleEndpointDevice|AccessibleEndpointDevice $element): DeviceEndpointDevice
    {
        $endpointDevice = $this->getRepository(DeviceEndpointDevice::class)->findOneBy([
            'name' => UniquenessTestCase::getElementOriginalName($element->key),
            'device' => $device,
        ]);
        $this->assertInstanceOf(DeviceEndpointDevice::class, $endpointDevice);

        return $endpointDevice;
    }
}
