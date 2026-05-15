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
 * Tests edit and visibility of access tags by VPN user on a single endpoint device.
 */
#[Group('full')]
#[Group('WebApiRoleVpn')] // To be used in CI parallel tests
class AccessTagsEndpointDeviceSingleTest extends AbstractTestCase
{
    public const SUCCESS = 'success';
    // One access tag is always required
    public const ERROR_400_ONEREQUIRED = 'error_400_oneRequired';
    // Access tag is invalid when it is not assigned to VPN user
    public const ERROR_400_INVALID = 'error_400_invalid';

    // Existing access tags
    public const _______AT_EXISTING = '_______AT_EXISTING';
    // User and device access tags
    public const ____AT_USER_DEVICE = '____AT_USER_DEVICE';
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
            TestFixtures\WebApi\UserAccess\RoleVpn\AccessTagsEndpointDeviceSingleFixtures::class,
        ];
    }

    /**
     * Read about data structure in getDataName().
     */
    public static function getData(): array
    {
        $data = [
            [
                static::SMOKE,
                static::_______AT_EXISTING => ['e'],
                static::____AT_USER_DEVICE => ['e'],
                static::AT_ENDPOINT_DEVICE => ['e'],
                static::______AT_SUBMITTED => ['e'],
                static::___________OUTCOME => static::SUCCESS,
                static::________AT_OUTCOME => ['e'],
                static::________AT_VISIBLE => ['e'],
            ],
            [
                static::SMOKE,
                static::_______AT_EXISTING => ['d', 'e'],
                static::____AT_USER_DEVICE => ['d', 'e'],
                static::AT_ENDPOINT_DEVICE => ['d'],
                static::______AT_SUBMITTED => [],
                static::___________OUTCOME => static::ERROR_400_ONEREQUIRED,
                static::________AT_OUTCOME => ['d'],
                static::________AT_VISIBLE => ['d'],
            ],
            [
                static::SMOKE,
                static::_______AT_EXISTING => ['d', 'e'],
                static::____AT_USER_DEVICE => ['d'],
                static::AT_ENDPOINT_DEVICE => ['d'],
                static::______AT_SUBMITTED => ['d', 'e'],
                static::___________OUTCOME => static::ERROR_400_INVALID,
                static::________AT_OUTCOME => ['d'],
                static::________AT_VISIBLE => ['d'],
            ],
            [
                static::SMOKE,
                static::_______AT_EXISTING => ['d', 'e'],
                static::____AT_USER_DEVICE => ['d'],
                static::AT_ENDPOINT_DEVICE => ['d', 'e'],
                static::______AT_SUBMITTED => ['d'],
                static::___________OUTCOME => static::SUCCESS,
                static::________AT_OUTCOME => ['d', 'e'],
                static::________AT_VISIBLE => ['d'],
            ],
            [
                static::_______AT_EXISTING => ['a', 'b', 'c', 'd', 'e'],
                static::____AT_USER_DEVICE => ['a', 'b', 'c', 'd'],
                static::AT_ENDPOINT_DEVICE => ['a', 'b', 'c', 'e'],
                static::______AT_SUBMITTED => ['c', 'a', 'd'],
                static::___________OUTCOME => static::SUCCESS,
                static::________AT_OUTCOME => ['a', 'c', 'd', 'e'],
                static::________AT_VISIBLE => ['a', 'c', 'd'],
            ],
            [
                static::_______AT_EXISTING => ['a', 'b', 'c', 'd', 'e'],
                static::____AT_USER_DEVICE => ['a', 'c', 'd'],
                static::AT_ENDPOINT_DEVICE => ['a', 'b', 'c', 'd', 'e'],
                static::______AT_SUBMITTED => ['c', 'a'],
                static::___________OUTCOME => static::SUCCESS,
                static::________AT_OUTCOME => ['a', 'b', 'c', 'e'],
                static::________AT_VISIBLE => ['a', 'c'],
            ],
        ];

        /**
         * We can generate all variations of 3 access tags for each for _______AT_EXISTING.
         *
         * Using _______AT_EXISTING we are generating all variations for each of following (separately)
         * ____AT_USER_DEVICE
         * AT_ENDPOINT_DEVICE
         * ______AT_SUBMITTED
         *
         * ___________OUTCOME,  ________AT_OUTCOME and ________AT_VISIBLE are determined by previous values.
         *
         * 3 access tags are used to ensure there will be cases with non-single access tags for removal or addition.
         */
        $ats = ['a', 'b', 'c'];

        foreach (static::getVariations($ats) as $existing) {
            foreach (static::getVariations($existing) as $userDevice) {
                foreach (static::getVariations($existing) as $endpointDevice) {
                    // Intersect will include any access tags that are both part of endpoint and userDevice access tags
                    $intersect = array_intersect($endpointDevice, $userDevice);
                    if (0 === count($intersect)) {
                        // Empty intersect means that use will not have access to this device. Skip such cases as they are tested elsewhere
                        continue;
                    }

                    foreach (static::getVariations($existing) as $submitted) {
                        // Diff will include any access tags that were submitted, but where not the part of userDevice access tags
                        $diff = array_diff($submitted, $userDevice);
                        if (count($diff) > 0) {
                            // Non-empty diff means we will have invalid outcome
                            $outcome = static::ERROR_400_INVALID;
                        } else {
                            $outcome = static::SUCCESS;
                        }

                        switch ($outcome) {
                            case static::SUCCESS:
                                // Endpoint device diff will include any access tags that were in endpointDevice, but where not the part of userDevice access tags. They should be kept after being submitted
                                $endpointDeviceDiff = array_diff($endpointDevice, $userDevice);

                                // Access tags outcome should be a merge of $endpointDeviceDiff and $submitted access tags
                                // They should be ordered as $existing access tags
                                $accessTagsOutcome = [];
                                foreach ($existing as $existingAt) {
                                    if (in_array($existingAt, $endpointDeviceDiff) || in_array($existingAt, $submitted)) {
                                        $accessTagsOutcome[] = $existingAt;
                                    }
                                }

                                // Intersect will include any access tags that are both part of outcome and userDevice access tags
                                $outcomeIntersect = array_intersect($accessTagsOutcome, $userDevice);
                                // Visible access tags are ones in access tags after outcome
                                $accessTagsVisible = $outcomeIntersect;
                                break;
                            case static::ERROR_400_INVALID:
                                $accessTagsOutcome = $endpointDevice;
                                // Visible access tags are ones that are both assigned to user and exist in endpoint device
                                $accessTagsVisible = array_intersect($endpointDevice, $userDevice);
                                break;
                            default:
                                throw new UnsupportedValueException($outcome);
                        }

                        $data[] = [
                            static::_______AT_EXISTING => $existing,
                            static::____AT_USER_DEVICE => $userDevice,
                            static::AT_ENDPOINT_DEVICE => $endpointDevice,
                            static::______AT_SUBMITTED => $submitted,
                            static::___________OUTCOME => $outcome,
                            static::________AT_OUTCOME => $accessTagsOutcome,
                            static::________AT_VISIBLE => $accessTagsVisible,
                        ];
                    }

                    // Include a case with empty submitted access tags
                    $data[] = [
                        static::_______AT_EXISTING => $existing,
                        static::____AT_USER_DEVICE => $userDevice,
                        static::AT_ENDPOINT_DEVICE => $endpointDevice,
                        static::______AT_SUBMITTED => [],
                        static::___________OUTCOME => static::ERROR_400_ONEREQUIRED,
                        static::________AT_OUTCOME => $endpointDevice,
                        // Visible access tags are ones that are both assigned to user and exist in endpoint device
                        static::________AT_VISIBLE => array_intersect($endpointDevice, $userDevice),
                    ];
                }
            }
        }

        // 6 (predefined) + 392 (variations) = 398 test cases
        return $data;
    }

    public static function dataProvider(): array
    {
        $data = static::getData();

        $namedData = static::getProviderNamedData($data, static::getDataName(...));

        static::validateData($namedData);

        $namedData = static::removeDataRowKeys($namedData);

        return $namedData;
    }

    public static function getDataName($row, $key): string
    {
        $nameParts = [];

        $nameParts[] = 'Expected outcome "'.$row[static::___________OUTCOME].'"';
        $nameParts[] = 'Existing access tags ['.implode(', ', $row[static::_______AT_EXISTING]).']';
        $nameParts[] = 'User and device access tags ['.implode(', ', $row[static::____AT_USER_DEVICE]).']';
        $nameParts[] = 'Endpoint device access tags ['.implode(', ', $row[static::AT_ENDPOINT_DEVICE]).']';
        $nameParts[] = 'Access tags submitted ['.implode(', ', $row[static::______AT_SUBMITTED]).']';
        $nameParts[] = 'Access tags expected ['.implode(', ', $row[static::________AT_OUTCOME]).']';
        $nameParts[] = 'Visible access tags expected ['.implode(', ', $row[static::________AT_VISIBLE]).']';

        return implode('. ', $nameParts);
    }

    public static function validateData(array $data): void
    {
        foreach ($data as $rowName => $row) {
            static::validateDataRow($rowName, $row);
        }
    }

    public static function validateDataRow(string $rowName, array $row): void
    {
        $diff = array_diff($row[static::____AT_USER_DEVICE], $row[static::_______AT_EXISTING]);
        if (count($diff) > 0) {
            throw new \Exception(static::____AT_USER_DEVICE.' includes access tag names "'.implode(', ', $diff).'" that are not included in '.static::_______AT_EXISTING.'. This may lead to errors in tests. Data row name "'.$rowName.'"');
        }

        $diff = array_diff($row[static::AT_ENDPOINT_DEVICE], $row[static::_______AT_EXISTING]);
        if (count($diff) > 0) {
            throw new \Exception(static::AT_ENDPOINT_DEVICE.' includes access tag names "'.implode(', ', $diff).'" that are not included in '.static::_______AT_EXISTING.'. This may lead to errors in tests. Data row name "'.$rowName.'"');
        }
    }

    #[DataProvider('dataProvider')]
    public function testEndpointDeviceEdit(array $accessTagNames, array $userAccessTagNames, array $endpointDeviceAccessTagNames, array $formAccessTagNames, string $outcome, array $outcomeAccessTagNames, array $visibleAccessTagNames)
    {
        $formAccessTagIds = $this->setUpAccessTags($accessTagNames, $userAccessTagNames, $endpointDeviceAccessTagNames, $formAccessTagNames);
        $endpointDevice = $this->getEndpointDevice();

        $this->loginUser();

        $payload = $this->getEndpointDevicePayload($endpointDevice, $userAccessTagNames);
        $payload['accessTags'] = $formAccessTagIds;

        $this->jsonPost('/web/api/deviceendpointdevice/'.$endpointDevice->getId(), $payload);

        $this->assertOutcome($outcome, 'errors.children.accessTags.errors.0.message');
        $this->assertEndpointDeviceAccessTags($outcomeAccessTagNames);

        if ($outcome === static::SUCCESS) {
            $this->assertVisibleAccessTags('accessTags', $visibleAccessTagNames);
        }
    }

    #[DataProvider('dataProvider')]
    public function testEndpointDeviceCollectionEdit(array $accessTagNames, array $userAccessTagNames, array $endpointDeviceAccessTagNames, array $formAccessTagNames, string $outcome, array $outcomeAccessTagNames, array $visibleAccessTagNames)
    {
        $formAccessTagIds = $this->setUpAccessTags($accessTagNames, $userAccessTagNames, $endpointDeviceAccessTagNames, $formAccessTagNames);
        $device = $this->getDevice();
        $endpointDevice = $this->getEndpointDevice();
        $endpointDeviceId = $endpointDevice->getId();

        $this->loginUser();

        $endpointDevicePayload = $this->getEndpointDevicePayload($endpointDevice, $userAccessTagNames);
        $endpointDevicePayload['accessTags'] = $formAccessTagIds;
        $payload = [
            'endpointDevices' => [$endpointDeviceId => $endpointDevicePayload],
        ];

        $this->jsonPost('/web/api/device/'.$device->getId(), $payload);

        $this->assertOutcome($outcome, 'errors.children.endpointDevices.children.'.$endpointDeviceId.'.children.accessTags.errors.0.message');
        $this->assertEndpointDeviceAccessTags($outcomeAccessTagNames);

        if ($outcome === static::SUCCESS) {
            $this->assertVisibleAccessTags('endpointDevices.0.accessTags', $visibleAccessTagNames);
        }
    }

    /**
     * @returns array<int> Array of access tag ids based on $formAccessTagNames
     */
    protected function setUpAccessTags(array $accessTagNames, array $userAccessTagNames, array $endpointDeviceAccessTagNames, array $formAccessTagNames): array
    {
        $endpointDevice = $this->getEndpointDevice();
        $user = $this->getUser();
        $device = $this->getDevice();
        $accessTags = [];
        $formAccessTagIds = [];

        foreach ($accessTagNames as $accessTagName) {
            $accessTag = new AccessTag();
            $accessTag->setName($accessTagName);

            $this->getEntityManager()->persist($accessTag);

            if (in_array($accessTagName, $userAccessTagNames)) {
                $user->getAccessTags()->add($accessTag);
                $this->getEntityManager()->persist($user);
            }

            if (in_array($accessTagName, $userAccessTagNames)) {
                $device->getAccessTags()->add($accessTag);
                $this->getEntityManager()->persist($device);
            }

            if (in_array($accessTagName, $endpointDeviceAccessTagNames)) {
                $endpointDevice->getAccessTags()->add($accessTag);
                $this->getEntityManager()->persist($endpointDevice);
            }

            $accessTags[$accessTagName] = $accessTag;
        }

        $this->getEntityManager()->flush();

        $formAccessTagIds = array_map(fn ($formAccessTagName) => $accessTags[$formAccessTagName]->getId(), $formAccessTagNames);

        return $formAccessTagIds;
    }

    protected function getEndpointDevicePayload(DeviceEndpointDevice $endpointDevice, array $userAccessTagNames): array
    {
        // Only use access tags that user has
        $accessTags = $endpointDevice->getAccessTags()->filter(fn (AccessTag $accessTag) => in_array($accessTag->getName(), $userAccessTagNames));
        // Convert to IDs and convert to a simple array that will not be converted to an object (keys are ordered)
        $accessTagIds = array_values($accessTags->map(fn (AccessTag $accessTag) => $accessTag->getId())->toArray());

        return [
            'name' => $endpointDevice->getName(),
            'physicalIp' => $endpointDevice->getPhysicalIp(),
            'virtualIpHostPart' => $endpointDevice->getVirtualIpHostPart(),
            'description' => $endpointDevice->getDescription(),
            'accessTags' => $accessTagIds,
        ];
    }

    protected function assertOutcome(string $outcome, string $errorKey): void
    {
        switch ($outcome) {
            case static::ERROR_400_ONEREQUIRED:
                $this->assertResponse400OneRequired($errorKey);
                break;
            case static::ERROR_400_INVALID:
                $this->assertResponse400Invalid($errorKey);
                break;
            case static::SUCCESS:
                $this->assertResponseIsSuccessfulJson();
                break;
            default:
                throw new UnsupportedValueException($outcome);
        }
    }

    protected function assertEndpointDeviceAccessTags(array $outcomeAccessTagNames, string $endpointDeviceName = 'ed'): void
    {
        // Always reload endpoint device before asserting
        $endpointDevice = $this->getFreshEndpointDevice($endpointDeviceName);
        $existingAccessTagNames = $endpointDevice->getAccessTags()->map(fn (AccessTag $accessTag) => $accessTag->getName())->toArray();
        $this->assertEqualsCanonicalizing($outcomeAccessTagNames, $existingAccessTagNames, 'Endpoint device access tags "'.implode(', ', $existingAccessTagNames).'" different then expected "'.implode(', ', $outcomeAccessTagNames).'"');
    }

    protected function assertVisibleAccessTags(string $key, array $visibleAccessTagNames): void
    {
        $contentAccessTags = Arr::get($this->getResponseContentAsArray(), $key);
        $contentAccessTagIds = array_map(fn ($contentAccessTag) => $contentAccessTag['id'], $contentAccessTags);
        $accessTagsById = $this->getAccessTagsById();
        $accessTagNames = array_map(fn ($accessTagId) => $accessTagsById[$accessTagId]->getName(), $contentAccessTagIds);

        $this->assertEqualsCanonicalizing($visibleAccessTagNames, $accessTagNames, 'Visible access tags "'.implode(', ', $accessTagNames).'" different then expected "'.implode(', ', $visibleAccessTagNames).'"');
    }

    protected function assertResponse400OneRequired(string $key): void
    {
        $this->assertResponse400();
        $this->assertResponseContentAsArrayValue('validation.endpointDevice.oneAccessTagRequired', $key);
    }

    protected function assertResponse400Invalid(string $key): void
    {
        $this->assertResponse400();
        $this->assertResponseContentAsArrayValue('validation.endpointDevice.invalidAccessTag', $key);
    }

    protected function loginUser(): void
    {
        $this->loginApi('vpn', 'vpn');
    }

    protected function getUser(): User
    {
        return $this->getRepository(User::class)->findOneBy(['username' => 'vpn']);
    }

    protected function getDevice(): Device
    {
        return $this->getRepository(Device::class)->findOneBy(['name' => 'd']);
    }

    protected function getEndpointDevice(string $endpointDeviceName = 'ed'): DeviceEndpointDevice
    {
        return $this->getRepository(DeviceEndpointDevice::class)->findOneBy(['name' => $endpointDeviceName]);
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

    /**
     * Doctrine keeps entity data cached internally, even after loading it from database it will be merged.
     * In order to get completely fresh DeviceEndpointDevice data, entity manager needs to be cleared.
     */
    protected function getFreshEndpointDevice(string $endpointDeviceName = 'ed'): ?DeviceEndpointDevice
    {
        $this->getEntityManager()->clear();

        return $this->getEndpointDevice($endpointDeviceName);
    }

    protected function convertAccessTagsToIds(array|Collection $accessTags): array
    {
        if ($accessTags instanceof Collection) {
            return $accessTags->map(fn (AccessTag $accessTag) => $accessTag->getId())->toArray();
        }

        return array_map(function (int|AccessTag $accessTag) {
            if ($accessTag instanceof AccessTag) {
                return $accessTag->getId();
            }

            return $accessTag;
        }, $accessTags);
    }
}
