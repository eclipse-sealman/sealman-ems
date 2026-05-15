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

namespace Tests\Utilities\DeviceCommunicationAbstract;

use App\Entity\Certificate;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\CertificateEntity;
use App\Service\FirmwareFileFactory;
use App\Service\PkiProviderFactory;
use App\Service\VpnProviderFactory;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\ApiClient\ApiClient;
use Tests\Utilities\DeviceCommunicationAbstract\Trait\AbstractDeviceCommunication\AssertionsTrait;
use Tests\Utilities\DeviceCommunicationAbstract\Trait\AbstractDeviceCommunication\EntitiesHandlingTrait;
use Tests\Utilities\DeviceCommunicationAbstract\Trait\AbstractDeviceCommunication\ServiceProxyTrait;
use Tests\Utilities\DeviceCommunicationApiClient\DeviceCommunicationApiClient;
use Tests\Utilities\Mock\PkiProvider\DevicesMockPkiProvider;
use Tests\Utilities\Mock\PkiProvider\TechniciansMockPkiProvider;
use Tests\Utilities\Mock\VpnProvider\MockVpnProvider;

/**
 * Base class for device communication tests.
 *
 * Used for integration and functional tests involving device communication with the system.
 * Assumes tests operate on a single device per test run.
 *
 * If methods are reused in other test cases, consider extracting to a trait.
 * todo add docs about what is expected to be provided for correct tests execution - to be added while refactoring device communication tests
 */
class AbstractDeviceCommunicationTestCase extends AbstractTestCase
{
    use AssertionsTrait;
    use ServiceProxyTrait;
    use EntitiesHandlingTrait;

    /**
     * Replace service providers with mocks for predictable test behavior.
     *
     * - VpnProviderFactory: always returns MockVpnProvider
     * - PkiProviderFactory: returns DevicesMockPkiProvider or TechniciansMockPkiProvider based on certificate entity
     * - FirmwareFileFactory: returns fixed file size and pseudo-random MD5 hash
     */
    public function mock(): void
    {
        // Mock VPN provider: always returns MockVpnProvider
        $mock = $this->createStub(VpnProviderFactory::class);
        $mock
            ->method('getProvider')
            ->willReturnCallback(function () {
                return new MockVpnProvider();
            })
        ;
        static::getContainer()->set(VpnProviderFactory::class, $mock);

        // Mock PKI provider: returns different mock providers based on certificate entity type
        $mock = $this->createStub(PkiProviderFactory::class);
        $mock
            ->method('getProvider')
            ->willReturnCallback(function (Certificate $certificate) {
                $certificateType = $certificate->getCertificateType();

                switch ($certificateType->getCertificateEntity()) {
                    case CertificateEntity::DEVICE:
                        return new DevicesMockPkiProvider();
                    case CertificateEntity::USER:
                        return new TechniciansMockPkiProvider();
                }

                throw new \Exception('Cannot determine mock PKI provider');
            })
        ;
        static::getContainer()->set(PkiProviderFactory::class, $mock);

        // Mock firmware file factory: returns fixed file size and MD5 hash
        $mock = $this->createStub(FirmwareFileFactory::class);
        $mock
            ->method('getFileSize')
            ->willReturnCallback(function (string $file) {
                return 1000; // Always returns 1000 bytes as file size
            })
        ;
        $mock
            ->method('getFileMd5')
            ->willReturnCallback(function (string $file) {
                return md5(' T '.time()); // Returns a pseudo-random MD5 hash
            })
        ;
        static::getContainer()->set(FirmwareFileFactory::class, $mock);
    }

    /**
     * Get the API client for device communication endpoints.
     */
    public function getApiClient(): ApiClient
    {
        if (null === $this->apiClient) {
            $this->apiClient = new DeviceCommunicationApiClient($this);
        }

        return $this->apiClient;
    }

    /**
     * List fixture groups required for device communication tests.
     */
    public static function getFixtureGroups(): array
    {
        return [
            'prod',
            TestFixtures\Configuration\ScepFixtures::class,
            TestFixtures\Configuration\VpnFixtures::class,
        ];
    }

    public function getRandomString(int $length = 8)
    {
        return bin2hex(random_bytes($length / 2));
    }

    public function getUniqueIdentifier(string $prefix): string
    {
        return $this->getRandomString().time().$prefix;
    }

    public function getUniqueDeviceIdentifier(DeviceType $deviceType): string
    {
        return $this->getUniqueIdentifier($deviceType->getSlug());
    }
}
