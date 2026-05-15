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

namespace Tests\Suites\Unit\SerializableJson;

use App\DataFixtures as ProdFixtures;
use App\Entity\Certificate;
use App\Entity\Config;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\AuthenticationMethod;
use App\Enum\CertificateEntity;
use App\Enum\CommunicationProcedure;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use App\Enum\MasqueradeType;
use App\Service\PkiProviderFactory;
use App\Service\VpnProviderFactory;
use Carve\ApiBundle\Helper\Arr;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Uid\Uuid;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\Mock\PkiProvider\DevicesMockPkiProvider;
use Tests\Utilities\Mock\VpnProvider\MockVpnProvider;

/**
 * Testing App\Serializer\Normalizer\SerializableJsonNormalizer class.
 *
 * Read more about it in mentioned class.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Unit')] // To be used in CI parallel tests
#[AllowMockObjectsWithoutExpectations]
class SerializableJsonTest extends AbstractTestCase
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
        ];
    }

    public function mock(): void
    {
        $mock = $this->createMock(VpnProviderFactory::class);
        $mock
            ->expects(self::any())
            ->method('getProvider')
            ->willReturnCallback(function () {
                return new MockVpnProvider();
            })
        ;
        static::getContainer()->set(VpnProviderFactory::class, $mock);

        $mock = $this->createMock(PkiProviderFactory::class);
        $mock
            ->expects(self::any())
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
    }

    public static function deviceTypeProvider(): array
    {
        return [
            'Edge gateway' => ['Edge gateway'],
            'Edge gateway with VPN Container Client' => ['Edge gateway with VPN Container Client'],
            'SG-gateway' => ['SG-gateway'],
        ];
    }

    protected function getConfigContents(): array
    {
        return [
            '{}',
            '{"empty":{}}',
            '{"notArray":{"0":"element0","1":"element1"}}',
        ];
    }

    #[DataProvider('deviceTypeProvider')]
    public function testSerializableJson(string $deviceTypeName)
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy([
            'name' => $deviceTypeName,
        ]);
        $this->assertInstanceOf(DeviceType::class, $deviceType);

        $deviceType->setAuthenticationMethod(AuthenticationMethod::NONE);
        $deviceType->setEnabled(true);
        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->flush();

        [$config, $device] = $this->setupDevice($deviceType);

        foreach ($this->getConfigContents() as $configContent) {
            $this->setupConfigContent($config, $configContent);
            $this->assertSerializedConfigContent($device, $configContent);
        }
    }

    /**
     * @return array<Config, Device>
     */
    protected function setupDevice(DeviceType $deviceType): array
    {
        $this->loginApi('admin', 'admin');

        $deviceTypeId = $deviceType->getId();
        $uuid = Uuid::v4()->toRfc4122();
        $uuidPart = substr($uuid, 0, 8);
        // TODO Bug. Too long name causes duplicates in certificateSubject name uniqueness
        $deviceTypeName = $uuidPart.substr($deviceType->getName(), 0, 10);

        $this->jsonPost('/web/api/config/create', [
            'name' => $deviceTypeName.' config '.$deviceTypeId,
            'deviceType' => $deviceType->getId(),
            'feature' => Feature::PRIMARY,
            'generator' => ConfigGenerator::TWIG,
            'content' => '{}', // Any valid content
        ]);
        $this->assertResponseIsSuccessfulJson();
        $configId = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($configId);

        $config = $this->getRepository(Config::class)->find($configId);
        $this->assertInstanceOf(Config::class, $config);

        $this->jsonPost('/web/api/template/create', [
            'name' => $deviceTypeName.' template '.$deviceTypeId,
            'deviceType' => $deviceType->getId(),
        ]);
        $this->assertResponseIsSuccessfulJson();
        $templateId = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($templateId);

        $this->jsonPost('/web/api/templateversion/create/staging/'.$templateId, [
            'name' => $deviceTypeName.' template version '.$deviceTypeId,
            'config1' => $configId,
        ]);
        $this->assertResponseIsSuccessfulJson();
        $templateVersionId = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($templateVersionId);
        $this->jsonPost('/web/api/templateversion/select/staging/'.$templateVersionId);
        $this->assertResponseIsSuccessfulJson();

        $serialNumber = $deviceTypeName.' Device '.$deviceTypeId;
        $payload = [
            'serialNumber' => $serialNumber,
            'name' => $serialNumber,
            'deviceType' => $deviceType->getId(),
            'template' => $templateId,
            'staging' => true,
            'enabled' => true,
        ];
        if ($deviceType->getIsEndpointDevicesAvailable()) {
            $payload['virtualSubnetCidr'] = 30;
        }
        if ($deviceType->getIsMasqueradeAvailable()) {
            $payload['masqueradeType'] = MasqueradeType::DISABLED;
        }

        $this->jsonPost('/web/api/device/create', $payload);
        $this->assertResponseIsSuccessfulJson();
        $deviceId = Arr::get($this->getResponseContentAsArray(), 'id');
        $this->assertIsInt($deviceId);

        $device = $this->getRepository(Device::class)->find($deviceId);
        $this->assertInstanceOf(Device::class, $device);

        return [$config, $device];
    }

    protected function setupConfigContent(Config $config, string $content): void
    {
        $this->loginApi('admin', 'admin');

        $this->jsonPost('/web/api/config/'.$config->getId(), [
            'name' => $config->getName(),
            'generator' => $config->getGenerator(),
            'content' => $content,
        ]);
        $this->assertResponseIsSuccessfulJson();
    }

    protected function assertSerializedConfigContent(Device $device, $expectedConfig): void
    {
        $this->loginApi('admin', 'admin');

        $this->jsonPost('/web/api/device/batch/reinstallconfig1', [
            'flag' => true,
            'ids' => [$device->getId()],
        ]);
        $this->assertResponseIsSuccessfulJson();

        $communicationProcedure = $device->getDeviceType()->getCommunicationProcedure();
        switch ($communicationProcedure) {
            case CommunicationProcedure::EDGEGATEWAY:
            case CommunicationProcedure::EDGEGATEWAY_WITH_VPNCONTAINERCLIENT:
                $this->assertEdgeGatewaySerializedConfigContent($device, $expectedConfig);
                break;
            case CommunicationProcedure::SGGATEWAY:
                $this->assertSgGatewaySerializedConfigContent($device, $expectedConfig);
                break;
            default:
                throw new \Exception('Unsupported communication procedure "'.$communicationProcedure->value.'"');
        }
    }

    protected function assertEdgeGatewaySerializedConfigContent(Device $device, $expectedConfig): void
    {
        $deviceType = $device->getDeviceType();
        $routePrefix = $deviceType->getValidRoutePrefix();

        $deviceId = $device->getId();
        $serialNumber = $device->getSerialNumber();

        $this->jsonPost($routePrefix.'/configuration', [
            'serialNumber' => $serialNumber,
            'endorsementKey' => 'EndorsementKey'.$deviceId,
            'hardwareVersion' => 'HardwareVersion'.$deviceId,
            'firmwareVersion' => 'FirmwareVersion'.$deviceId,
            'registrationId' => 'RegistrationId'.$deviceId,
            'networkGeneration' => 'NetworkGeneration'.$deviceId,
            'imei' => 'IMEI'.$deviceId,
            'imsi' => 'IMSI'.$deviceId,
        ]);
        $this->assertResponseIsSuccessfulJson();

        // Do not parse JSON response as \json_decode() converts i.e. '{"empty": {}}' into '["empty": []]' which is NOT what we want to test here. We want to find whether config is serialized and returned correctly before any processing our our side.
        $content = $this->getResponseContent();
        $this->assertStringContainsString($expectedConfig, $content);
    }

    protected function assertSgGatewaySerializedConfigContent(Device $device, $expectedConfig): void
    {
        $deviceType = $device->getDeviceType();
        $routePrefix = $deviceType->getValidRoutePrefix();

        $deviceId = $device->getId();
        $serialNumber = $device->getSerialNumber();

        $this->jsonPost($routePrefix.'/configuration', [
            'serialNumber' => $serialNumber,
            'hardwareVersion' => 'HardwareVersion'.$deviceId,
            'firmwareVersion' => 'FirmwareVersion'.$deviceId,
        ]);
        $this->assertResponseIsSuccessfulJson();

        // Do not parse JSON response as \json_decode() converts i.e. '{"empty": {}}' into '["empty": []]' which is NOT what we want to test here. We want to find whether config is serialized and returned correctly before any processing our our side.
        $content = $this->getResponseContent();
        $this->assertStringContainsString($expectedConfig, $content);
    }
}
