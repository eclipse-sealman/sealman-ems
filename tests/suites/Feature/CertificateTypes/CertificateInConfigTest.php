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

namespace Tests\Suites\Feature\CertificateTypes;

use App\Entity\Config;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use App\Enum\TemplateVersionType;
use Carve\ApiBundle\Helper\Arr;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Uid\Uuid;
use Tests\Utilities\Abstract\AbstractCertificateTypeTestCase;
use Tests\Utilities\Provider\DeviceTypeProvider;

#[Group('full')]
#[Group('smoke')]
#[Group('Feature')] // To be used in CI parallel tests
class CertificateInConfigTest extends AbstractCertificateTypeTestCase
{
    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testCertificateInConfig(string $deviceTypeName)
    {
        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        // skip test for VCC - config cannot handle additional certificates at this point
        if ('VPN Container Client' == $this->deviceType->getName()) {
            return;
        }

        // Generate SCEP Certificate
        $this->loginApi('admin', 'admin');

        $this->requestCertificateGeneration();

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        // Get config and check certificate

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->setupDeviceForConfigDownload();

        $this->requestDeviceCommunication();

        $this->assertResponseIsSuccessful();

        $this->assertTrue(false !== openssl_x509_parse($this->getCertificateValueFromResponse()));

        $certificateArray = openssl_x509_parse($this->getCertificateValueFromResponse());
        $certificateSerial = $certificateArray['serialNumber'];

        // Fake close to expire Get config and check certificate serial - expecting new one
        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->assertFalse($this->device->getReinstallConfig1());
        // faking close to expire validity date
        foreach ($this->device->getCertificates() as $certificate) {
            if ($certificate->getCertificateType() == $this->certificateType) {
                $certificate->setCertificateValidTo(new \DateTime('+ 4 days'));
                $this->getEntityManager()->persist($certificate);
                break;
            }
        }

        $this->device->setReinstallConfig1(true);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->requestDeviceCommunication();

        $this->assertResponseIsSuccessful();
        $this->assertTrue(false !== openssl_x509_parse($this->getCertificateValueFromResponse()));
        $certificateArray = openssl_x509_parse($this->getCertificateValueFromResponse());
        $certificateNewSerial = $certificateArray['serialNumber'];

        $this->assertNotEquals($certificateNewSerial, $certificateSerial);

        $certificateSerial = $certificateNewSerial;

        $this->loginApi('admin', 'admin');

        // Alter altName settings in deviceType - to force certificate renewal on next communication
        $this->requestGetDeviceType();
        $deviceTypeProperties = $this->getResponseContentAsArray();
        $newDeviceTypeProperties = [];

        $keysToCopy = [
            'color',
            'configMinRsrp',
            'connectionAggregationPeriod',
            'deviceName',
            'enableConfigLogs',
            'enableConfigMinRsrp',
            'enableConnectionAggregation',
            'enableFirmwareMinRsrp',
            'firmwareMinRsrp',
            'icon',
            'name',
            'virtualSubnetCidr',
            'deviceCommandMaxRetries',
            'deviceCommandExpireDuration',
            'authenticationMethod',
            'credentialsSource',
            'deviceTypeSecretCredential',
            'deviceTypeCertificateTypeCredential',
            'deviceTypeCertificateTypeMTlsScepAuthentication',
        ];

        if (true === Arr::get($deviceTypeProperties, 'hasFirmware1')) {
            $keysToCopy[] = 'firmwareSchema1';
        }

        if (true === Arr::get($deviceTypeProperties, 'hasFirmware2')) {
            $keysToCopy[] = 'firmwareSchema2';
        }

        if (true === Arr::get($deviceTypeProperties, 'hasFirmware3')) {
            $keysToCopy[] = 'firmwareSchema3';
        }

        foreach ($keysToCopy as $key) {
            if (isset($deviceTypeProperties[$key])) {
                $newDeviceTypeProperties[$key] = $deviceTypeProperties[$key];
            }
        }

        if (!Arr::get($newDeviceTypeProperties, 'enableConfigMinRsrp', false)) {
            unset($newDeviceTypeProperties['configMinRsrp']);
        }

        if (!Arr::get($newDeviceTypeProperties, 'enableFirmwareMinRsrp', false)) {
            unset($newDeviceTypeProperties['firmwareMinRsrp']);
        }

        if (!Arr::get($deviceTypeProperties, 'hasVpn', false) || !Arr::get($deviceTypeProperties, 'hasEndpointDevices', false)) {
            unset($newDeviceTypeProperties['virtualSubnetCidr']);
        }

        if (!Arr::get($deviceTypeProperties, 'hasDeviceCommands', false)) {
            unset($newDeviceTypeProperties['deviceCommandMaxRetries']);
            unset($newDeviceTypeProperties['deviceCommandExpireDuration']);
        }

        // certificateType
        $keysToCopy = [
            'certificateEncoding',
            'certificatesAutoRenewDaysBefore',
            'enableCertificatesAutoRenew',
            'enableSubjectAltName',
        ];

        $newDeviceTypeProperties['certificateTypes'] = [];

        $deviceTypeCertificateTypes = Arr::get($deviceTypeProperties, 'certificateTypes', []);

        foreach ($deviceTypeCertificateTypes as $certificateType) {
            $newCertificateType = [];
            foreach ($keysToCopy as $key) {
                if (isset($certificateType[$key])) {
                    $newCertificateType[$key] = $certificateType[$key];
                }
            }
            if ($newCertificateType['enableSubjectAltName']) {
                if (isset($certificateType['subjectAltNameType'])) {
                    $newCertificateType['subjectAltNameType'] = $certificateType['subjectAltNameType'];
                }
                if (isset($certificateType['subjectAltNameValue'])) {
                    $newCertificateType['subjectAltNameValue'] = $certificateType['subjectAltNameValue'];
                }
            }

            $newCertificateType['certificateType'] = $certificateType['certificateType']['id'];
            if ($newCertificateType['certificateType'] == $this->certificateType->getId()) {
                // making an update
                $newCertificateType['enableSubjectAltName'] = true;
                $newCertificateType['subjectAltNameType'] = 'DNS';
                $newCertificateType['subjectAltNameValue'] = time();
            }
            $newDeviceTypeProperties['certificateTypes'][] = $newCertificateType;
        }

        $this->requestLimitedEditDeviceType($newDeviceTypeProperties);
        $this->assertResponseIsSuccessful();

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->assertFalse($this->device->getReinstallConfig1());

        $this->device->setReinstallConfig1(true);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->requestDeviceCommunication();

        $this->assertResponseIsSuccessful();
        $this->assertTrue(false !== openssl_x509_parse($this->getCertificateValueFromResponse()));
        $certificateArray = openssl_x509_parse($this->getCertificateValueFromResponse());
        $certificateNewSerial = $certificateArray['serialNumber'];

        $this->assertNotEquals($certificateNewSerial, $certificateSerial);
    }

    public function setupDeviceForConfigDownload()
    {
        $this->getEntityManager()->flush();

        $config = new Config();
        $config->setDeviceType($this->deviceType);
        $config->setFeature(Feature::PRIMARY);
        $config->setGenerator(ConfigGenerator::TWIG);
        $config->setName($this->deviceType->getName().'-config');
        $config->setContent($this->getConfigContent());
        $config->setUuid(Uuid::v4()->toRfc4122()); // no additional checking for duplication

        $template = new Template();
        $template->setName($this->deviceType->getName().'-template-config');
        $template->setDeviceType($this->deviceType);

        $templateVersion = new TemplateVersion();
        $templateVersion->setType(TemplateVersionType::PRODUCTION);
        $templateVersion->setName($this->deviceType->getName().'-template-config-v1');
        $templateVersion->setDescription($this->deviceType->getName().'-template-config-desc-v1');
        $templateVersion->setDeviceDescription($this->deviceType->getName().'-template-config-dev-v1');
        $templateVersion->setDeviceType($this->deviceType);
        $templateVersion->setTemplate($template);
        $templateVersion->setConfig1($config);

        $template->setProductionTemplate($templateVersion);

        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->persist($templateVersion);
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        $this->device->setTemplate($template);
        $this->device->setEnabled(true); // no vpn update
        $this->device->setReinstallConfig1(true);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();
    }

    public function getJsonCertificateValueFromResponse(): string
    {
        $array = $this->getResponseContentAsArray();
        if (isset($array['config']['certificate']) && is_string($array['config']['certificate']) && !empty($array['config']['certificate'])) {
            return $array['config']['certificate'];
        }

        return 'N/A';
    }

    public function getCertificateValueFromResponse(): string
    {
        switch ($this->deviceType->getName()) {
            case 'Edge gateway': return $this->getJsonCertificateValueFromResponse();
            case 'TK800': return $this->getResponseContent();
            case 'TK500': return $this->getResponseContent();
            case 'VPN Container Client': return $this->getJsonCertificateValueFromResponse();
            case 'Edge gateway with VPN Container Client': return $this->getJsonCertificateValueFromResponse();
        }
    }

    public function getConfigContent(): string
    {
        switch ($this->deviceType->getName()) {
            case 'Edge gateway': return '{"certificate":"{{ scepCertificatePlain }}"}';
            case 'TK800': return '{{ scepCertificatePlain }}';
            case 'TK500': return '{{ scepCertificatePlain }}';
            case 'VPN Container Client': return '{"certificate":"{{ scepCertificatePlain }}"}';
            case 'Edge gateway with VPN Container Client': return '{"certificate":"{{ scepCertificatePlain }}"}';
        }
    }

    public function getDeviceUsername(): string
    {
        switch ($this->deviceType->getName()) {
            case 'Edge gateway': return 'edgeGateway';
            case 'TK800': return 'router';
            case 'TK500': return 'router';
            case 'VPN Container Client': return 'vpnContainerClient';
            case 'Edge gateway with VPN Container Client': return 'edgeGateway';
        }
    }

    public function getDeviceUserPassword(): string
    {
        switch ($this->deviceType->getName()) {
            case 'Edge gateway': return '123456';
            case 'TK800': return '123456';
            case 'TK500': return '123456';
            case 'VPN Container Client': return '123456';
            case 'Edge gateway with VPN Container Client': return '123456';
        }
    }

    public function getDeviceCommunicationUrl(): string
    {
        switch ($this->deviceType->getName()) {
            case 'Edge gateway': return $this->deviceType->getRoutePrefix().'/configuration';
            case 'TK800': return $this->deviceType->getRoutePrefix().'/config';
            case 'TK500': return $this->deviceType->getRoutePrefix().'/config';
            case 'VPN Container Client': return $this->deviceType->getRoutePrefix().'/configuration/'.$this->device->getUuid();
            case 'Edge gateway with VPN Container Client': return $this->deviceType->getRoutePrefix().'/configuration';
        }
    }

    public function requestDeviceCommunication(): void
    {
        if (in_array($this->deviceType->getName(), ['TK800', 'TK500'])) {
            $postData = [
                'Firmware' => '1.0',
                'Serial' => $this->device->getSerialNumber(),
            ];

            $this->loginDigestAuth($this->getDeviceUsername(), $this->getDeviceUserPassword());
            // Any request from now will be authenticated as "router" (will include digest authentication)

            $this->post($this->getDeviceCommunicationUrl(), $postData);
        } else {
            $this->loginBasicAuth($this->getDeviceUsername(), $this->getDeviceUserPassword());

            $postData = [
                'firmwareVersion' => '1.0',
                'serialNumber' => $this->device->getSerialNumber(),
                'registrationId' => 'REG'.$this->device->getSerialNumber(),
                'endorsementKey' => 'KEY'.$this->device->getSerialNumber(),
                'hardwareVersion' => 'HW'.$this->device->getSerialNumber(),
            ];

            $this->jsonPost($this->getDeviceCommunicationUrl(), $postData);
        }
    }
}
