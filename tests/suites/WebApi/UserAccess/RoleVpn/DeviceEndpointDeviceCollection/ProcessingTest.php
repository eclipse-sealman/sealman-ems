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
use App\Entity\User;
use App\Enum\AuditLogChangeType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Feature\AuditLog\AssertAuditLogTrait;
use Tests\Utilities\WebApi\UserAccess\AccessibleEndpointDevice;
use Tests\Utilities\WebApi\UserAccess\Action;
use Tests\Utilities\WebApi\UserAccess\NonAccessibleEndpointDevice;
use Tests\Utilities\WebApi\UserAccess\ProcessingTestCase;

/**
 * This class tests processing of endpoint device collection and state of audit logs afterwards.
 * All of those tests are done by user with VPN permissions allowed to manage endpoint devices.
 *
 * There are 3 types of elements in a collection:
 * - null which assumes this collection element is empty (missing, does not exist)
 * - AccessibleEndpointDevice which describes ED that is accessible to VPN user
 * - NonAccessibleEndpointDevice which describes ED that is NOT accessible to VPN user
 *
 * null and NonAccessibleEndpointDevice is expected:
 * - Not to be changed by VPN user
 * - Not to generate audit log
 *
 * EndpointDevice is further described by an action:
 * - OMITTED - Element is omitted in payload
 * - UNCHANGED - Element is not changed in payload
 * - CREATE - Element is created in payload
 * - UPDATE - Element is updated in payload
 * - DELETE - Element is deleted in payload
 *
 * AccessibleEndpointDevice and NonAccessibleEndpointDevice are defined by a numerical key (1-5) to ensure uniqueness and human readability of data.
 *
 * Following setup is used for each element in collection:
 * - Element 1: null or NonAccessibleEndpointDevice with OMITTED, UNCHANGED or UPDATE action
 * - Element 2: null or AccessibleEndpointDevice with one of UNCHANGED, CREATE, UPDATE or DELETE action
 * - Element 3: null or NonAccessibleEndpointDevice with OMITTED, UNCHANGED or UPDATE action
 * - Element 4: null or AccessibleEndpointDevice with of UNCHANGED, CREATE, UPDATE or DELETE action
 * - Element 5: null or NonAccessibleEndpointDevice with OMITTED, UNCHANGED or UPDATE action
 *
 * For each test case a device is created with relevant endpoint devices.
 * Audit logs after fixtures are loaded are REMOVED to have easier asserts while testing.
 *
 * Note! Elements 1,3,5 should have a test with both UNCHANGED and UPDATE action. UNCHANGED action could pass the test due to invalid data being sent (UNCHANGED would send access tags that are invalid from VPN user perspective and trigger 400 solely based on that and not due to access denied).
 */
#[Group('full')]
#[Group('WebApiRoleVpn')] // To be used in CI parallel tests
class ProcessingTest extends AbstractTestCase
{
    use AssertAuditLogTrait;

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
            TestFixtures\WebApi\UserAccess\RoleVpn\DeviceEndpointDeviceCollection\ProcessingTestFixtures::class,
        ];
    }

    public static function removeAuditLogsAfterFixtures(): bool
    {
        return true;
    }

    public static function getTestCases(): array
    {
        $elements1 = [
            null,
            new NonAccessibleEndpointDevice(1, Action::OMITTED),
            new NonAccessibleEndpointDevice(1, Action::UNCHANGED),
            new NonAccessibleEndpointDevice(1, Action::UPDATE),
        ];
        $elements2 = [
            null,
            new AccessibleEndpointDevice(2, Action::UNCHANGED),
            new AccessibleEndpointDevice(2, Action::CREATE),
            new AccessibleEndpointDevice(2, Action::UPDATE),
            new AccessibleEndpointDevice(2, Action::DELETE),
        ];
        $elements3 = [
            null,
            new NonAccessibleEndpointDevice(3, Action::OMITTED),
            new NonAccessibleEndpointDevice(3, Action::UNCHANGED),
            new NonAccessibleEndpointDevice(3, Action::UPDATE),
        ];
        $elements4 = [
            null,
            new AccessibleEndpointDevice(4, Action::UNCHANGED),
            new AccessibleEndpointDevice(4, Action::CREATE),
            new AccessibleEndpointDevice(4, Action::UPDATE),
            new AccessibleEndpointDevice(4, Action::DELETE),
        ];
        $elements5 = [
            null,
            new NonAccessibleEndpointDevice(5, Action::OMITTED),
            new NonAccessibleEndpointDevice(5, Action::UNCHANGED),
            new NonAccessibleEndpointDevice(5, Action::UPDATE),
        ];

        $cases = [];

        foreach (static::getArrayCombinations([$elements1, $elements2, $elements3, $elements4, $elements5]) as $key => $value) {
            $cases[] = new ProcessingTestCase('device'.$key, $value[0], $value[1], $value[2], $value[3], $value[4]);
        }

        return $cases;
    }

    public static function dataProvider(): array
    {
        $data = array_map(fn ($testCase) => [$testCase], static::getTestCases());

        $data = static::markSmoke($data, function ($row, $key) {
            $testCase = $row[0];

            // Mark as smoke NonAccessibleEndpointDevice with OMITTED action and a few other (mostly positive test cases)
            return in_array($testCase->element1?->action, [Action::OMITTED])
            && in_array($testCase->element3?->action, [Action::OMITTED, Action::UNCHANGED, Action::UPDATE])
            && in_array($testCase->element5?->action, [Action::OMITTED]);
        });

        $namedData = static::getProviderNamedData($data, static::getDataName(...));

        return $namedData;
    }

    public static function getDataName($row, $key): string
    {
        $testCase = $row[0];

        $nameParts = [];
        $nameParts[] = 'Data #'.$key;
        $nameParts[] = 'Processing test case';

        foreach ($testCase->getElements() as $key => $element) {
            $nameParts[] = 'Element '.$key.' = '.ProcessingTestCase::getDataElementName($element);
        }

        return implode('. ', $nameParts);
    }

    #[DataProvider('dataProvider')]
    public function testProcessing(ProcessingTestCase $testCase)
    {
        $this->loginApi('vpn', 'vpn');

        $device = $this->getDevice($testCase);
        $payload = $this->getPayload($testCase);

        $initialEndpointDeviceIds = [];
        foreach ($testCase->getElements() as $key => $element) {
            $endpointDevice = $device->getEndpointDevices()->findFirst(fn (int $index, DeviceEndpointDevice $endpointDevice) => $endpointDevice->getName() === ProcessingTestCase::getElementOriginalName($key));
            $initialEndpointDeviceIds[$key] = $endpointDevice?->getId();
        }

        $this->jsonPost('/web/api/device/'.$device->getId(), $payload);

        $isValid = true;
        foreach ($testCase->getElements() as $element) {
            // Any other action then OMITTED for NonAccessibleEndpointDevice should result in invalid processing
            if ($element instanceof NonAccessibleEndpointDevice && Action::OMITTED !== $element->action) {
                $isValid = false;
                break;
            }
        }

        if ($isValid) {
            $this->assertValidProcessing($testCase, $initialEndpointDeviceIds);
        } else {
            $this->assertInvalidProcessing($testCase, $initialEndpointDeviceIds);
        }
    }

    protected function getPayload(ProcessingTestCase $testCase): array
    {
        $device = $this->getDevice($testCase);
        $payload = [
            'endpointDevices' => [],
        ];

        foreach ($testCase->getElements() as $element) {
            if (null === $element) {
                continue;
            }

            if ($element instanceof NonAccessibleEndpointDevice) {
                // We need to preserve keys returned by function
                $payload['endpointDevices'] = $payload['endpointDevices'] + $this->getNonAccessibleEndpointDevicePayload($device, $element);
                continue;
            }

            if ($element instanceof AccessibleEndpointDevice) {
                // We need to preserve keys returned by function
                $payload['endpointDevices'] = $payload['endpointDevices'] + $this->getAccessibleEndpointDevicePayload($device, $element);
                continue;
            }

            throw new \LogicException('Unsupported element');
        }

        return $payload;
    }

    /**
     * Returns indexed array with endpoint device payload [ID => payload].
     */
    protected function getNonAccessibleEndpointDevicePayload(Device $device, NonAccessibleEndpointDevice $element): array
    {
        $key = $element->key;
        $payload = [];

        switch ($element->action) {
            case Action::OMITTED:
                // Exclude from payload to omit
                break;
            case Action::UNCHANGED:
                $endpointDevice = $this->getEndpointDevice($device, $element);
                $payload[$endpointDevice->getId()] = [
                    'name' => ProcessingTestCase::getElementOriginalName($key),
                    'physicalIp' => $endpointDevice->getPhysicalIp(),
                    'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
                    'accessTags' => $endpointDevice->getAccessTags()->map(fn (AccessTag $accessTag) => $accessTag->getId())->toArray(),
                ];
                break;
            case Action::UPDATE:
                $endpointDevice = $this->getEndpointDevice($device, $element);
                $payload[$endpointDevice->getId()] = [
                    'name' => ProcessingTestCase::getElementUpdatedName($key),
                    'physicalIp' => $endpointDevice->getPhysicalIp(),
                    'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
                    'accessTags' => [$this->getVpnAccessTag()->getId()],
                ];
                break;
            default:
                static::throwUnsupportedEnum($element->action);
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
                    'name' => ProcessingTestCase::getElementOriginalName($key),
                    'physicalIp' => $endpointDevice->getPhysicalIp(),
                    'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
                    'accessTags' => $endpointDevice->getAccessTags()->map(fn (AccessTag $accessTag) => $accessTag->getId())->toArray(),
                ];
                break;
            case Action::CREATE:
                $payload['new_'.$key] = [
                    'name' => ProcessingTestCase::getElementOriginalName($key),
                    'physicalIp' => '1.1.1.'.$key,
                    'virtualIpHostPart' => $key,
                    'accessTags' => [$this->getVpnAccessTag()->getId()],
                ];
                break;
            case Action::UPDATE:
                $endpointDevice = $this->getEndpointDevice($device, $element);
                $payload[$endpointDevice->getId()] = [
                    'name' => ProcessingTestCase::getElementUpdatedName($key),
                    'physicalIp' => $endpointDevice->getPhysicalIp(),
                    'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
                    'accessTags' => [$this->getVpnAccessTag()->getId()],
                ];
                break;
            case Action::DELETE:
                // Exclude from payload to delete
                break;
            default:
                static::throwUnsupportedEnum($element->action);
        }

        return $payload;
    }

    protected function assertValidProcessing(ProcessingTestCase $testCase, array $initialEndpointDeviceIds): void
    {
        $this->assertResponseIsSuccessfulJson();

        $this->getEntityManager()->clear();
        $device = $this->getDevice($testCase);
        $endpointDevicesCount = 0;

        foreach ($testCase->getElements() as $key => $element) {
            $initialId = $initialEndpointDeviceIds[$key];

            if (null === $element) {
                $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                $this->assertEntityNull($processedElement);

                $this->assertNull($initialId);
                // Cannot assert audit log change as element was and is not present
                continue;
            }

            if ($element instanceof NonAccessibleEndpointDevice) {
                $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                $this->assertInstanceOf(DeviceEndpointDevice::class, $processedElement);

                $this->assertSame($processedElement->getId(), $initialId);
                $this->assertNoChange(DeviceEndpointDevice::class, $initialId);

                ++$endpointDevicesCount;
                continue;
            }

            if ($element instanceof AccessibleEndpointDevice) {
                switch ($element->action) {
                    case Action::UNCHANGED:
                        $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                        $this->assertInstanceOf(DeviceEndpointDevice::class, $processedElement);

                        $this->assertSame($processedElement->getId(), $initialId);
                        $this->assertNoChange(DeviceEndpointDevice::class, $initialId);

                        ++$endpointDevicesCount;
                        break;
                    case Action::CREATE:
                        $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                        $this->assertInstanceOf(DeviceEndpointDevice::class, $processedElement);

                        $this->assertNull($initialId);
                        $change = $this->findChange(DeviceEndpointDevice::class, $processedElement->getId());
                        $this->assertChange($change, AuditLogChangeType::CREATE, null, [
                            'id' => $processedElement->getId(),
                            'name' => ProcessingTestCase::getElementOriginalName($key),
                        ]);

                        ++$endpointDevicesCount;
                        break;
                    case Action::UPDATE:
                        $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementUpdatedName($key));
                        $this->assertInstanceOf(DeviceEndpointDevice::class, $processedElement);

                        $this->assertSame($processedElement->getId(), $initialId);
                        $change = $this->findChange(DeviceEndpointDevice::class, $initialId);
                        $this->assertChange($change, AuditLogChangeType::UPDATE, [
                            'id' => $initialId,
                            'name' => ProcessingTestCase::getElementOriginalName($key),
                        ], [
                            'id' => $initialId,
                            'name' => ProcessingTestCase::getElementUpdatedName($key),
                        ]);

                        ++$endpointDevicesCount;
                        break;
                    case Action::DELETE:
                        $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                        $this->assertEntityNull($processedElement);

                        $this->assertNotNull($initialId);
                        $change = $this->findChange(DeviceEndpointDevice::class, $initialId);
                        $this->assertChange($change, AuditLogChangeType::DELETE, [
                            'id' => $initialId,
                            'name' => ProcessingTestCase::getElementOriginalName($key),
                        ], null);
                        break;
                    default:
                        static::throwUnsupportedEnum($element->action);
                }

                continue;
            }

            throw new \LogicException('Unsupported element');
        }

        $this->assertSame($endpointDevicesCount, $device->getEndpointDevices()->count());
    }

    protected function assertInvalidProcessing(ProcessingTestCase $testCase, array $initialEndpointDeviceIds): void
    {
        $this->assertResponse400();

        $this->getEntityManager()->clear();
        $device = $this->getDevice($testCase);
        $endpointDevicesCount = 0;

        foreach ($testCase->getElements() as $key => $element) {
            $initialId = $initialEndpointDeviceIds[$key];

            if (null === $element) {
                $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                $this->assertEntityNull($processedElement);

                $this->assertNull($initialId);
                // Cannot assert audit log change as element was and is not present
                continue;
            }

            if ($element instanceof NonAccessibleEndpointDevice) {
                // No changes are expected for all actions
                $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                $this->assertInstanceOf(DeviceEndpointDevice::class, $processedElement);

                $this->assertSame($processedElement->getId(), $initialId);
                $this->assertNoChange(DeviceEndpointDevice::class, $initialId);

                ++$endpointDevicesCount;
                continue;
            }

            if ($element instanceof AccessibleEndpointDevice) {
                switch ($element->action) {
                    case Action::CREATE:
                        $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                        $this->assertEntityNull($processedElement);

                        $this->assertNull($initialId);
                        // Cannot assert audit log change as element was and is not present
                        break;
                    case Action::UNCHANGED:
                    case Action::UPDATE:
                    case Action::DELETE:
                        // No changes are expected for all actions
                        $processedElement = $this->getProcessedElement($device, ProcessingTestCase::getElementOriginalName($key));
                        $this->assertInstanceOf(DeviceEndpointDevice::class, $processedElement);

                        $this->assertSame($processedElement->getId(), $initialId);
                        $this->assertNoChange(DeviceEndpointDevice::class, $initialId);

                        ++$endpointDevicesCount;
                        break;
                    default:
                        static::throwUnsupportedEnum($element->action);
                }

                continue;
            }

            throw new \LogicException('Unsupported element');
        }

        $this->assertSame($endpointDevicesCount, $device->getEndpointDevices()->count());
    }

    protected function getDevice(ProcessingTestCase $testCase): Device
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
            'name' => ProcessingTestCase::getElementOriginalName($element->key),
            'device' => $device,
        ]);
        $this->assertInstanceOf(DeviceEndpointDevice::class, $endpointDevice);

        return $endpointDevice;
    }

    protected function getProcessedElement(Device $device, string $name): ?DeviceEndpointDevice
    {
        return $this->getRepository(DeviceEndpointDevice::class)->findOneBy([
            'name' => $name,
            'device' => $device,
        ]);
    }
}
