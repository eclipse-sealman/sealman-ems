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

namespace Tests\Suites\Unit\DeviceSecret;

use App\DataFixtures as ProdFixtures;
use App\DeviceCommunication\DeviceCommunicationFactory;
use App\DeviceCommunication\DeviceCommunicationInterface;
use App\Entity\Device;
use App\Entity\DeviceSecret;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeSecret;
use App\Service\DeviceSecretManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Tests CommunicationProcedure getDeviceSecretVariables() method with different device types.
 *
 * Verifies whether values will be properly decrypted and obfuscated.
 */
#[Group('full')]
#[Group('smoke')]
class DeviceSecretVariableValueTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\DeviceAuthenticationFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
            ProdFixtures\UserDeviceSecretCredentialsFixtures::class,
            ProdFixtures\UserDeviceX509CredentialsFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\Configuration\ScepFixtures::class,
            TestFixtures\Configuration\VpnFixtures::class,
            TestFixtures\User\AdminFixtures::class,
            TestFixtures\DeviceSecret\DeviceFixtures::class,
        ];
    }

    /**
     * Provides rows as follows:
     * [$deviceTypeName, $deviceTypeSecretCount, $deviceSecretCount].
     *
     * Limited to following counts:
     * [$deviceTypeName, 0-3, 0-$deviceTypeSecretCount].
     */
    public static function provider(): array
    {
        // TODO Refactor it. Use probably data directly from Fixtures (consts?)
        $deviceTypeNames = [
            'Edge gateway',
            'TK800',
            'TK500',
            'VPN Container Client',
            'Edge gateway with VPN Container Client',
        ];

        $data = [];

        foreach ($deviceTypeNames as $deviceTypeName) {
            for ($deviceTypeSecretCount = 0; $deviceTypeSecretCount <= 3; ++$deviceTypeSecretCount) {
                for ($deviceSecretCount = 0; $deviceSecretCount <= $deviceTypeSecretCount; ++$deviceSecretCount) {
                    $data[] = [$deviceTypeName, $deviceTypeSecretCount, $deviceSecretCount];
                }
            }
        }

        return $data;
    }

    /**
     * Method runs before asserts and populates:
     * - DeviceType with DeviceTypeSecret (amount = $deviceTypeSecretCount)
     * - Device with DeviceSecret (amount = $deviceSecretCount) using $secretValueCallback callable to generate secret values.
     */
    protected function populate(string $deviceTypeName, int $deviceTypeSecretCount, int $deviceSecretCount, callable $secretValueCallback): void
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]);
        $device = $this->getRepository(Device::class)->findOneBy(['serialNumber' => $deviceTypeName]);

        $this->createDeviceTypeSecrets($deviceType, $deviceTypeSecretCount);
        $this->createDeviceSecrets($device, $deviceSecretCount, $secretValueCallback);
    }

    /**
     * Decrypted secret values should NOT be obfuscated.
     */
    #[DataProvider('provider')]
    public function testDecrypted(string $deviceTypeName, int $deviceTypeSecretCount, int $deviceSecretCount)
    {
        $this->populate($deviceTypeName, $deviceTypeSecretCount, $deviceSecretCount, [$this, 'getSecretValue']);

        $communicationProcedure = $this->getCommunicationProcedure($deviceTypeName);

        $secretVariables = $communicationProcedure->getDeviceSecretVariables(decryptSecretValues: true);
        $this->assertCount($deviceSecretCount * 6, $secretVariables);

        for ($index = 0; $index < $deviceSecretCount; ++$index) {
            $expectedSecretValue = $this->getSecretValue($index);

            $prefix = $this->getVariableNamePrefix($index);

            $this->assertArrayHasKey($prefix.'Plain', $secretVariables);
            $this->assertEquals($secretVariables[$prefix.'Plain'], $expectedSecretValue);

            $this->assertArrayHasKey($prefix.'Base64', $secretVariables);
            $this->assertEquals($secretVariables[$prefix.'Base64'], base64_encode($expectedSecretValue));

            $this->assertArrayHasKey($prefix.'CryptMd5', $secretVariables);
            $this->assertTrue(password_verify($expectedSecretValue, $secretVariables[$prefix.'CryptMd5']));

            $this->assertArrayHasKey($prefix.'CryptBlowFish', $secretVariables);
            $this->assertTrue(password_verify($expectedSecretValue, $secretVariables[$prefix.'CryptBlowFish']));

            $this->assertArrayHasKey($prefix.'CryptSha256', $secretVariables);
            $this->assertTrue(password_verify($expectedSecretValue, $secretVariables[$prefix.'CryptSha256']));

            $this->assertArrayHasKey($prefix.'CryptSha512', $secretVariables);
            $this->assertTrue(password_verify($expectedSecretValue, $secretVariables[$prefix.'CryptSha512']));
        }
    }

    /**
     * Encrypted secret values should be obfuscated.
     */
    #[DataProvider('provider')]
    public function testEncrypted(string $deviceTypeName, int $deviceTypeSecretCount, int $deviceSecretCount)
    {
        $this->populate($deviceTypeName, $deviceTypeSecretCount, $deviceSecretCount, [$this, 'getSecretValue']);

        $communicationProcedure = $this->getCommunicationProcedure($deviceTypeName);

        $secretVariables = $communicationProcedure->getDeviceSecretVariables(decryptSecretValues: false);
        $this->assertCount($deviceSecretCount * 6, $secretVariables);

        for ($index = 0; $index < $deviceSecretCount; ++$index) {
            $expectedSecretValue = $this->getSecretValue($index);

            $prefix = $this->getVariableNamePrefix($index);
            $obscuredValue = 'ObscuredValue';

            $this->assertArrayHasKey($prefix.'Plain', $secretVariables);
            $this->assertNotEquals($secretVariables[$prefix.'Plain'], $expectedSecretValue);
            $this->assertEquals($secretVariables[$prefix.'Plain'], 'Plain'.$obscuredValue);

            $this->assertArrayHasKey($prefix.'Base64', $secretVariables);
            $this->assertNotEquals($secretVariables[$prefix.'Base64'], base64_encode($expectedSecretValue));
            $this->assertEquals($secretVariables[$prefix.'Base64'], 'Base64'.$obscuredValue.'==');

            $this->assertArrayHasKey($prefix.'CryptMd5', $secretVariables);
            $this->assertFalse(password_verify($expectedSecretValue, $secretVariables[$prefix.'CryptMd5']));
            $this->assertEquals($secretVariables[$prefix.'CryptMd5'], '$1$'.$obscuredValue);

            $this->assertArrayHasKey($prefix.'CryptBlowFish', $secretVariables);
            $this->assertFalse(password_verify($expectedSecretValue, $secretVariables[$prefix.'CryptBlowFish']));
            $this->assertEquals($secretVariables[$prefix.'CryptBlowFish'], '$2y$10$'.$obscuredValue);

            $this->assertArrayHasKey($prefix.'CryptSha256', $secretVariables);
            $this->assertFalse(password_verify($expectedSecretValue, $secretVariables[$prefix.'CryptSha256']));
            $this->assertEquals($secretVariables[$prefix.'CryptSha256'], '$5$'.$obscuredValue);

            $this->assertArrayHasKey($prefix.'CryptSha512', $secretVariables);
            $this->assertFalse(password_verify($expectedSecretValue, $secretVariables[$prefix.'CryptSha512']));
            $this->assertEquals($secretVariables[$prefix.'CryptSha512'], '$6$'.$obscuredValue);
        }
    }

    /**
     * Null secret values should be obfuscated.
     */
    #[DataProvider('provider')]
    public function testSecretValueNull(string $deviceTypeName, int $deviceTypeSecretCount, int $deviceSecretCount)
    {
        $this->populate($deviceTypeName, $deviceTypeSecretCount, $deviceSecretCount, fn () => null);

        $communicationProcedure = $this->getCommunicationProcedure($deviceTypeName);

        $secretVariables = $communicationProcedure->getDeviceSecretVariables(decryptSecretValues: false);
        $this->assertCount($deviceSecretCount * 6, $secretVariables);

        for ($index = 0; $index < $deviceSecretCount; ++$index) {
            $prefix = $this->getVariableNamePrefix($index);
            $obscuredValue = 'ObscuredValue';

            $this->assertArrayHasKey($prefix.'Plain', $secretVariables);
            $this->assertEquals($secretVariables[$prefix.'Plain'], 'Plain'.$obscuredValue);

            $this->assertArrayHasKey($prefix.'Base64', $secretVariables);
            $this->assertEquals($secretVariables[$prefix.'Base64'], 'Base64'.$obscuredValue.'==');

            $this->assertArrayHasKey($prefix.'CryptMd5', $secretVariables);
            $this->assertEquals($secretVariables[$prefix.'CryptMd5'], '$1$'.$obscuredValue);

            $this->assertArrayHasKey($prefix.'CryptBlowFish', $secretVariables);
            $this->assertEquals($secretVariables[$prefix.'CryptBlowFish'], '$2y$10$'.$obscuredValue);

            $this->assertArrayHasKey($prefix.'CryptSha256', $secretVariables);
            $this->assertEquals($secretVariables[$prefix.'CryptSha256'], '$5$'.$obscuredValue);

            $this->assertArrayHasKey($prefix.'CryptSha512', $secretVariables);
            $this->assertEquals($secretVariables[$prefix.'CryptSha512'], '$6$'.$obscuredValue);
        }
    }

    protected function getCommunicationProcedure(string $deviceTypeName): DeviceCommunicationInterface
    {
        $device = $this->getRepository(Device::class)->findOneBy(['serialNumber' => $deviceTypeName]);
        $deviceCommunicationFactory = $this->getService(DeviceCommunicationFactory::class);

        return $deviceCommunicationFactory->getDeviceCommunicationByDevice($device);
    }

    protected function createDeviceTypeSecrets(DeviceType $deviceType, int $maxCount): void
    {
        for ($index = 0; $index < $maxCount; ++$index) {
            $deviceTypeSecret = new DeviceTypeSecret();
            $deviceTypeSecret->setName('Secret'.$index);
            $deviceTypeSecret->setDescription('DescriptionSecret'.$index);
            $deviceTypeSecret->setVariableNamePrefix($this->getVariableNamePrefix($index));
            $deviceTypeSecret->setUseAsVariable(true);
            $deviceTypeSecret->setDeviceType($deviceType);

            $deviceType->getDeviceTypeSecrets()->add($deviceTypeSecret);
        }

        // Flushing is not necessary as all processing is done on entities without interacting with database
    }

    protected function createDeviceSecrets(Device $device, int $maxCount, callable $secretValueCallback): void
    {
        $deviceSecretManager = $this->getService(DeviceSecretManager::class);
        $deviceType = $device->getDeviceType();

        for ($index = 0; $index < $maxCount; ++$index) {
            $deviceSecret = new DeviceSecret();
            $deviceSecret->setDeviceTypeSecret($deviceType->getDeviceTypeSecrets()[$index]);
            $deviceSecret->setDevice($device);

            $deviceSecret->setSecretValue($secretValueCallback($index));

            $deviceSecretManager->encryptDeviceSecret($deviceSecret);

            $device->getDeviceSecrets()->add($deviceSecret);
        }

        // Flushing is not necessary as all processing is done on entities without interacting with database
    }

    protected function getVariableNamePrefix(int $index): string
    {
        return 's'.$index;
    }

    protected function getSecretValue(int $index): string
    {
        return 'secret'.$index.'Value';
    }
}
