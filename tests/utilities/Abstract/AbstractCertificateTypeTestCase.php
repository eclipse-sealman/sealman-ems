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

namespace Tests\Utilities\Abstract;

use App\DataFixtures as ProdFixtures;
use App\Entity\Certificate;
use App\Entity\CertificateType;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\User;
use App\Enum\CertificateEntity;
use App\Service\PkiProviderFactory;
use App\Service\VpnProviderFactory;
use App\Tool\Urlizer;
use Carve\ApiBundle\Helper\Arr;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Mock\PkiProvider\DevicesMockPkiProvider;
use Tests\Utilities\Mock\PkiProvider\TechniciansMockPkiProvider;
use Tests\Utilities\Mock\VpnProvider\MockVpnProvider;

/**
 * Abstract class specifically to use with CertificateType tests
 * If some methods are needed elsewhere, please move them to trait.
 * Method assumes working with one device at a time.
 */
class AbstractCertificateTypeTestCase extends AbstractTestCase
{
    // Preloaded data for tests
    protected CertificateType $certificateType;
    protected DeviceType $deviceType;
    protected Device $device;
    protected User $user;

    public const SCEP_TEST_CERTIFICATE_TYPE = 'SCEP-Test';
    public const SCEP_USER_TEST_CERTIFICATE_TYPE = 'SCEP-User-Test';

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
            TestFixtures\CertificateTypes\CertificateTypeFixtures::class,
            TestFixtures\CertificateTypes\DeviceTypeFixtures::class,
            TestFixtures\CertificateTypes\DeviceFixtures::class,
        ];
    }

    public function mock(): void
    {
        $mock = $this->createStub(VpnProviderFactory::class);
        $mock
            ->method('getProvider')
            ->willReturnCallback(function () {
                return new MockVpnProvider();
            })
        ;
        static::getContainer()->set(VpnProviderFactory::class, $mock);

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
    }

    /**
     * Method loads device, deviceType and certificateType objects into this properties (or reports error).
     */
    public function loadDeviceFromFixtures(string $certificateTypeName, string $deviceTypeName)
    {
        if (isset($this->certificateType)) {
            $this->getEntityManager()->detach($this->certificateType);
        }
        $this->certificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => $certificateTypeName]);
        $this->assertNotNull($this->certificateType);

        if (isset($this->deviceType)) {
            $this->getEntityManager()->detach($this->deviceType);
        }
        $this->deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]);
        $this->assertNotNull($this->deviceType);

        if (isset($this->device)) {
            $this->getEntityManager()->detach($this->device);
        }
        $this->device = $this->getRepository(Device::class)->findOneBy(['serialNumber' => $deviceTypeName]);
        $this->assertNotNull($this->device);
    }

    /**
     * Method loads deviceType and certificateType objects into this properties (or reports error).
     */
    public function loadDeviceTypeFromFixtures(string $certificateTypeName, string $deviceTypeName)
    {
        if (isset($this->certificateType)) {
            $this->getEntityManager()->detach($this->certificateType);
        }
        $this->certificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => $certificateTypeName]);
        $this->assertNotNull($this->certificateType);

        if (isset($this->deviceType)) {
            $this->getEntityManager()->detach($this->deviceType);
        }
        $this->deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]);
        $this->assertNotNull($this->deviceType);
    }

    /**
     * Method loads user and certificateType objects into this properties (or reports error).
     */
    public function loadUserFromFixtures(string $certificateTypeName, string $userName = 'admin-ct-fixtures')
    {
        if (isset($this->certificateType)) {
            $this->getEntityManager()->detach($this->certificateType);
        }
        $this->certificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => $certificateTypeName]);
        $this->assertNotNull($this->certificateType);

        // Adding same code to user as above, freezes user tests - don't know root cause. Probably something related to doctrine and blamable.
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => $userName]);
        $this->assertNotNull($this->user);
    }

    /**
     * Method executes api call for device certificate generation (based on this properties).
     */
    public function requestCertificateGeneration(?int $certificateTypeId = null)
    {
        $this->jsonGet('/web/api/device/'.$this->device->getId().'/'.($certificateTypeId ?: $this->certificateType->getId()).'/generate/certificate');
    }

    /**
     * Method executes api call for device certificate revocation (based on this properties).
     */
    public function requestCertificateRevocation(?int $certificateTypeId = null)
    {
        $this->jsonGet('/web/api/device/'.$this->device->getId().'/'.($certificateTypeId ?: $this->certificateType->getId()).'/revoke/certificate');
    }

    /**
     * Method executes api call for get device (based on this properties).
     */
    public function requestGetDevice()
    {
        $this->jsonGet('/web/api/device/'.$this->device->getId());
    }

    /**
     * Method executes api call for get deviceType (based on this properties).
     */
    public function requestGetDeviceType()
    {
        $this->jsonGet('/web/api/devicetype/'.$this->deviceType->getId());
    }

    /**
     * Method executes api call for limitedEdit deviceType (based on this properties).
     */
    public function requestLimitedEditDeviceType(array $postData)
    {
        $this->jsonPost('/web/api/devicetype/limitededit/'.$this->deviceType->getId(), $postData);
    }

    /**
     * Method executes api call for limitedEdit deviceType (based on this properties).
     */
    public function requesEditDeviceType(array $postData)
    {
        $this->jsonPost('/web/api/devicetype/'.$this->deviceType->getId(), $postData);
    }

    /**
     * Method executes api call for edit device (based on this properties).
     */
    public function requestEditDevice(array $postData)
    {
        $this->jsonPost('/web/api/device/'.$this->device->getId(), $postData);
    }

    /**
     * Method executes api call for enable device (based on this properties).
     */
    public function requestEnableDevice(array $postData)
    {
        $this->jsonPost('/web/api/device/'.$this->device->getId().'/enable', $postData);
    }

    /**
     * Method executes api call for disable device (based on this properties).
     */
    public function requestDisableDevice(array $postData)
    {
        $this->jsonPost('/web/api/device/'.$this->device->getId().'/disable', $postData);
    }

    /**
     * Method executes api call for get user (based on this properties).
     */
    public function requestGetUser()
    {
        $this->jsonGet('/web/api/user/'.$this->user->getId());
    }

    /**
     * Method executes api call for edit user (based on this properties).
     */
    public function requestEditUser(array $postData)
    {
        $this->jsonPost('/web/api/user/'.$this->user->getId(), $postData);
    }

    /**
     * Method executes api call for enable user (based on this properties).
     */
    public function requestEnableUser(array $postData)
    {
        $this->jsonPost('/web/api/user/enable/'.$this->user->getId(), $postData);
    }

    /**
     * Method executes api call for disable user (based on this properties).
     */
    public function requestDisableUser(array $postData)
    {
        $this->jsonPost('/web/api/user/disable/'.$this->user->getId(), $postData);
    }

    /**
     * Method executes api call for user certificate generation (based on this properties).
     */
    public function requestUserCertificateGeneration(?int $certificateTypeId = null)
    {
        $this->jsonGet('/web/api/user/'.$this->user->getId().'/'.($certificateTypeId ?: $this->certificateType->getId()).'/generate/certificate');
    }

    /**
     * Method executes api call for user certificate revocation (based on this properties).
     */
    public function requestUserCertificateRevocation(?int $certificateTypeId = null)
    {
        $this->jsonGet('/web/api/user/'.$this->user->getId().'/'.($certificateTypeId ?: $this->certificateType->getId()).'/revoke/certificate');
    }

    /**
     * Method executes api call to edit to enable/disable device with possibility to check or uncheck generate/revoke flag (based on this properties) - using device edit.
     * Set $certificateTypeId will forcefuly add row in usableCertificates with flag. Method returns key of added $certificateTypeId.
     */
    public function updateDeviceEnable(bool $enabled, ?bool $pkiFlag = null, ?int $certificateTypeId = null): ?int
    {
        $this->requestGetDevice();

        $deviceProperties = $this->getResponseContentAsArray();
        $newDeviceProperties = [];

        $keysToCopy = [
            'virtualSubnetCidr',
            'masqueradeType',
            'description',
            'template',
            'name',
            'serialNumber',
            'imsi',
            'imei',
            'registrationId',
            'endorsementKey',
            'hardwareVersion',
            'model',
            'reinstallFirmware1',
            'reinstallFirmware2',
            'reinstallFirmware3',
            'reinstallConfig1',
            'reinstallConfig2',
            'reinstallConfig3',
            'requestDiagnoseData',
            'requestConfigData',
            'variables',
            'staging',
            'accessTags',
        ];

        foreach ($keysToCopy as $key) {
            if (isset($deviceProperties[$key])) {
                $newDeviceProperties[$key] = $deviceProperties[$key];
            }
        }

        $newDeviceProperties['enabled'] = $enabled;

        if ($newDeviceProperties['enabled']) {
            $pkiKey = 'generateCertificate';
        } else {
            $pkiKey = 'revokeCertificate';
        }

        $newDeviceProperties['certificateBehaviours'] = [];
        foreach ($deviceProperties['useableCertificates'] as $certificateType) {
            $newCertificateType = [];
            $newCertificateType['certificateType'] = $certificateType['certificateType']['id'];
            if (null !== $pkiFlag && $newCertificateType['certificateType'] == $this->certificateType->getId()) {
                $newCertificateType[$pkiKey] = $pkiFlag;
            }
            $newDeviceProperties['certificateBehaviours'][] = $newCertificateType;
        }

        $returnValue = null;
        if ($certificateTypeId) {
            $found = false;
            foreach ($newDeviceProperties['certificateBehaviours'] as $key => $certificateType) {
                if ($certificateType['certificateType'] == $certificateTypeId) {
                    $found = true;
                    $returnValue = $key;
                    break;
                }
            }
            if (!$found) {
                $newCertificateType = [];
                $newCertificateType['certificateType'] = $certificateTypeId;
                if (null !== $pkiFlag) {
                    $newCertificateType[$pkiKey] = $pkiFlag;
                }
                $newDeviceProperties['certificateBehaviours'][] = $newCertificateType;
                $returnValue = array_search($newCertificateType, $newDeviceProperties['certificateBehaviours']);
            }
        }

        $this->requestEditDevice($newDeviceProperties);

        return $returnValue;
    }

    /**
     * Method executes api call to edit deviceType with possibility to check or uncheck certificateType hasCertificate flag (based on this properties) - using deviceType edit.
     * Set $certificateTypeId will forcefuly add row in certificateType with flag. Method returns key of added $certificateTypeId.
     */
    public function updateDeviceType(bool $enabled, ?int $certificateTypeId = null): ?int
    {
        $this->requestGetDeviceType();

        $deviceProperties = $this->getResponseContentAsArray();
        $newDeviceProperties = [];

        $keysToCopyTk800 = [
            'name',
            'deviceName',
            'icon',
            'color',
            'certificateCommonNamePrefix',
            'enableConfigLogs',

            'routePrefix',
            'authenticationMethod',
            'credentialsSource',
            'communicationProcedure',
            'enableConnectionAggregation',
            'connectionAggregationPeriod',

            'hasFirmware1',
            'nameFirmware1',
            'customUrlFirmware1',
            'hasConfig1',
            'nameConfig1',
            'formatConfig1',
            'hasConfig2',
            'hasAlwaysReinstallConfig2',
            'nameConfig2',
            'formatConfig2',

            'hasTemplates',
            'hasEndpointDevices',
            'hasGsm',
            'hasRequestDiagnose',
            'fieldRegistrationId',
            'fieldEndorsementKey',
            'fieldHardwareVersion',
            'fieldModel',
            'fieldSerialNumber',
            'fieldImsi',
            'hasVariables',
            'hasCertificates',
            'hasVpn',
        ];

        $keysToCopyTk500 = [
            'name',
            'deviceName',
            'icon',
            'color',
            'certificateCommonNamePrefix',
            'enableConfigLogs',

            'routePrefix',
            'authenticationMethod',
            'credentialsSource',
            'communicationProcedure',
            'enableConnectionAggregation',
            'connectionAggregationPeriod',

            'hasFirmware1',
            'nameFirmware1',
            'customUrlFirmware1',
            'hasConfig1',
            'nameConfig1',
            'formatConfig1',

            'hasTemplates',
            'hasEndpointDevices',
            'hasGsm',
            'hasRequestDiagnose',
            'fieldRegistrationId',
            'fieldEndorsementKey',
            'fieldHardwareVersion',
            'fieldModel',
            'fieldSerialNumber',
            'fieldImsi',

            'hasVariables',
            'hasCertificates',
            'hasVpn',
        ];

        $keysToCopyEg = [
            'name',
            'deviceName',
            'icon',
            'color',
            'certificateCommonNamePrefix',
            'enableConfigLogs',

            'routePrefix',
            'authenticationMethod',
            'credentialsSource',
            'communicationProcedure',
            'enableConnectionAggregation',
            'connectionAggregationPeriod',

            'hasFirmware1',
            'nameFirmware1',
            'customUrlFirmware1',
            'hasConfig1',
            'nameConfig1',
            'formatConfig1',

            'hasTemplates',
            'hasEndpointDevices',
            'hasGsm',
            'hasRequestConfig',
            'fieldRegistrationId',
            'fieldEndorsementKey',
            'fieldHardwareVersion',
            'fieldModel',
            'fieldSerialNumber',
            'fieldImsi',

            'hasVariables',
            'hasCertificates',
            'hasVpn',
            'hasDeviceCommands',
        ];

        $keysToCopyEgVcc = [
            'name',
            'deviceName',
            'icon',
            'color',
            'certificateCommonNamePrefix',
            'enableConfigLogs',

            'routePrefix',
            'authenticationMethod',
            'credentialsSource',
            'communicationProcedure',
            'enableConnectionAggregation',
            'connectionAggregationPeriod',

            'hasFirmware1',
            'nameFirmware1',
            'customUrlFirmware1',
            'hasConfig1',
            'nameConfig1',
            'formatConfig1',

            'hasTemplates',
            'hasEndpointDevices',
            'hasGsm',
            'hasRequestConfig',
            'hasMasquerade',
            'fieldRegistrationId',
            'fieldEndorsementKey',
            'fieldHardwareVersion',
            'fieldModel',
            'fieldSerialNumber',
            'fieldImsi',

            'hasVariables',
            'hasCertificates',
            'hasVpn',
            'hasDeviceCommands',
        ];

        $keysToCopyVcc = [
            'name',
            'deviceName',
            'icon',
            'color',
            'certificateCommonNamePrefix',
            'enableConfigLogs',

            'routePrefix',
            'authenticationMethod',
            'credentialsSource',
            'communicationProcedure',
            'enableConnectionAggregation',
            'connectionAggregationPeriod',

            'hasTemplates',
            'hasEndpointDevices',
            'hasMasquerade',
            'fieldRegistrationId',
            'fieldEndorsementKey',
            'fieldHardwareVersion',
            'fieldModel',
            'fieldSerialNumber',
            'fieldImsi',

            'hasVariables',
            'hasCertificates',
            'hasVpn',
        ];

        switch ($this->deviceType->getName()) {
            case 'Edge gateway':
                $keysToCopy = $keysToCopyEg;
                break;
            case 'TK800':
                $keysToCopy = $keysToCopyTk800;
                break;
            case 'TK500':
                $keysToCopy = $keysToCopyTk500;
                break;
            case 'VPN Container Client':
                $keysToCopy = $keysToCopyVcc;
                break;
            case 'Edge gateway with VPN Container Client':
                $keysToCopy = $keysToCopyEgVcc;
                break;
        }

        if ($deviceProperties['hasFirmware1']) {
            $keysToCopy[] = 'firmwareSchema1';
            $keysToCopy[] = 'allowDowngradeFirmware1';
        }

        if ($deviceProperties['hasFirmware2']) {
            $keysToCopy[] = 'firmwareSchema2';
            $keysToCopy[] = 'allowDowngradeFirmware2';
        }

        if ($deviceProperties['hasFirmware3']) {
            $keysToCopy[] = 'firmwareSchema3';
            $keysToCopy[] = 'allowDowngradeFirmware3';
        }

        foreach ($keysToCopy as $key) {
            if (isset($deviceProperties[$key])) {
                $newDeviceProperties[$key] = $deviceProperties[$key];
            }
        }

        if ($deviceProperties['hasConfig1']) {
            $newDeviceProperties['enableConfigMinRsrp'] = true;
            $newDeviceProperties['configMinRsrp'] = $deviceProperties['configMinRsrp'];
        }

        if ($deviceProperties['hasFirmware1']) {
            $newDeviceProperties['enableFirmwareMinRsrp'] = true;
            $newDeviceProperties['firmwareMinRsrp'] = $deviceProperties['firmwareMinRsrp'];
        }

        if ($deviceProperties['hasDeviceCommands']) {
            $newDeviceProperties['deviceCommandMaxRetries'] = $deviceProperties['deviceCommandMaxRetries'];
            $newDeviceProperties['deviceCommandExpireDuration'] = $deviceProperties['deviceCommandExpireDuration'];
        }

        if ($deviceProperties['hasMasquerade']) {
            $newDeviceProperties['masqueradeType'] = $deviceProperties['masqueradeType'];
        }

        if ($deviceProperties['hasEndpointDevices']) {
            $newDeviceProperties['virtualSubnetCidr'] = $deviceProperties['virtualSubnetCidr'];
        }

        $newDeviceProperties['certificateTypes'] = [];
        foreach ($deviceProperties['certificateTypes'] as $certificateType) {
            $newCertificateType = [];
            $newCertificateType['certificateType'] = $certificateType['certificateType']['id'];

            $newCertificateType['certificateEncoding'] = $certificateType['certificateEncoding'];
            $newCertificateType['enableCertificatesAutoRenew'] = $certificateType['enableCertificatesAutoRenew'];
            $newCertificateType['enableSubjectAltName'] = $certificateType['enableSubjectAltName'];

            if ($newCertificateType['enableCertificatesAutoRenew']) {
                $newCertificateType['certificatesAutoRenewDaysBefore'] = $certificateType['certificatesAutoRenewDaysBefore'];
            }

            if ($newCertificateType['enableSubjectAltName']) {
                $newCertificateType['subjectAltNameType'] = $certificateType['subjectAltNameType'];
            }

            if ($newCertificateType['certificateType'] == $this->certificateType->getId()) {
                if ($enabled) {
                    $newDeviceProperties['certificateTypes'][] = $newCertificateType;
                }
            } else {
                $newDeviceProperties['certificateTypes'][] = $newCertificateType;
            }
        }

        $returnValue = null;
        if ($certificateTypeId) {
            $found = false;
            foreach ($newDeviceProperties['certificateTypes'] as $key => $certificateType) {
                if ($certificateType['certificateType'] == $certificateTypeId) {
                    $found = true;
                    $returnValue = $key;
                    break;
                }
            }
            if (!$found) {
                $newCertificateType = [];
                $newCertificateType['certificateType'] = $certificateTypeId;
                $newCertificateType['certificateEncoding'] = 'hex';
                $newCertificateType['enableCertificatesAutoRenew'] = false;
                $newCertificateType['enableSubjectAltName'] = false;

                if ($enabled) {
                    $newDeviceProperties['certificateTypes'][] = $newCertificateType;
                    $returnValue = array_search($newCertificateType, $newDeviceProperties['certificateTypes']);
                }
            }
        }

        $this->requesEditDeviceType($newDeviceProperties);

        return $returnValue;
    }

    /**
     * Method executes api call to enable device with possibility to check or uncheck generate/revoke flag (based on this properties) - using device edit.
     * Set $certificateTypeId will forcefuly add row in usableCertificates with flag. Method returns key of added $certificateTypeId.
     */
    public function enableDevice(?bool $pkiFlag = null, ?int $certificateTypeId = null): ?int
    {
        $this->requestGetDevice();

        $deviceProperties = $this->getResponseContentAsArray();
        $newDeviceProperties = [];

        $pkiKey = 'generateCertificate';

        $newDeviceProperties['certificateBehaviours'] = [];
        foreach ($deviceProperties['useableCertificates'] as $certificateType) {
            $newCertificateType = [];
            $newCertificateType['certificateType'] = $certificateType['certificateType']['id'];
            if (null !== $pkiFlag && $newCertificateType['certificateType'] == $this->certificateType->getId()) {
                $newCertificateType[$pkiKey] = $pkiFlag;
            }
            $newDeviceProperties['certificateBehaviours'][] = $newCertificateType;
        }

        $returnValue = null;
        if ($certificateTypeId) {
            $found = false;
            foreach ($newDeviceProperties['certificateBehaviours'] as $key => $certificateType) {
                if ($certificateType['certificateType'] == $certificateTypeId) {
                    $found = true;
                    $returnValue = $key;
                    break;
                }
            }
            if (!$found) {
                $newCertificateType = [];
                $newCertificateType['certificateType'] = $certificateTypeId;
                if (null !== $pkiFlag) {
                    $newCertificateType[$pkiKey] = $pkiFlag;
                }
                $newDeviceProperties['certificateBehaviours'][] = $newCertificateType;
                $returnValue = array_search($newCertificateType, $newDeviceProperties['certificateBehaviours']);
            }
        }

        $this->requestEnableDevice($newDeviceProperties);

        return $returnValue;
    }

    /**
     * Method executes api call to disable device with possibility to check or uncheck generate/revoke flag (based on this properties) - using device edit.
     * Set $certificateTypeId will forcefuly add row in usableCertificates with flag. Method returns key of added $certificateTypeId.
     */
    public function disableDevice(?bool $pkiFlag = null, ?int $certificateTypeId = null): ?int
    {
        $this->requestGetDevice();

        $deviceProperties = $this->getResponseContentAsArray();
        $newDeviceProperties = [];

        $pkiKey = 'revokeCertificate';

        $newDeviceProperties['certificateBehaviours'] = [];
        foreach ($deviceProperties['useableCertificates'] as $certificateType) {
            $newCertificateType = [];
            $newCertificateType['certificateType'] = $certificateType['certificateType']['id'];
            if (null !== $pkiFlag && $newCertificateType['certificateType'] == $this->certificateType->getId()) {
                $newCertificateType[$pkiKey] = $pkiFlag;
            }
            $newDeviceProperties['certificateBehaviours'][] = $newCertificateType;
        }

        $returnValue = null;
        if ($certificateTypeId) {
            $found = false;
            foreach ($newDeviceProperties['certificateBehaviours'] as $key => $certificateType) {
                if ($certificateType['certificateType'] == $certificateTypeId) {
                    $found = true;
                    $returnValue = $key;
                    break;
                }
            }
            if (!$found) {
                $newCertificateType = [];
                $newCertificateType['certificateType'] = $certificateTypeId;
                if (null !== $pkiFlag) {
                    $newCertificateType[$pkiKey] = $pkiFlag;
                }
                $newDeviceProperties['certificateBehaviours'][] = $newCertificateType;
                $returnValue = array_search($newCertificateType, $newDeviceProperties['certificateBehaviours']);
            }
        }

        $this->requestDisableDevice($newDeviceProperties);

        return $returnValue;
    }

    /**
     * Method executes api call to edit to enable/disable user with possibility to check or uncheck generate/revoke flag (based on this properties) - using user edit.
     * Set $certificateTypeId will forcefuly add row in usableCertificates with flag. Method returns key of added $certificateTypeId.
     */
    public function updateUserEnable(bool $enabled, ?bool $pkiFlag = null, ?int $certificateTypeId = null): ?int
    {
        $this->requestGetUser();

        $userProperties = $this->getResponseContentAsArray();
        $newUserProperties = [];

        $keysToCopy = [
            'username',
            'enabledExpireAt',
            'disablePasswordExpire',
            'totpEnabled',
            'roleAdmin',
            'roleSmartems',
            'roleVpn',
            'accessTags',
        ];

        foreach ($keysToCopy as $key) {
            if (isset($userProperties[$key])) {
                $newUserProperties[$key] = $userProperties[$key];
            }
        }

        $newUserProperties['enabled'] = $enabled;

        if ($newUserProperties['enabled']) {
            $pkiKey = 'generateCertificate';
        } else {
            $pkiKey = 'revokeCertificate';
        }

        $newUserProperties['certificateBehaviours'] = [];
        foreach ($userProperties['useableCertificates'] as $certificateType) {
            $newCertificateType = [];
            $newCertificateType['certificateType'] = $certificateType['certificateType']['id'];
            if (null !== $pkiFlag && $newCertificateType['certificateType'] == $this->certificateType->getId()) {
                $newCertificateType[$pkiKey] = $pkiFlag;
            }
            $newUserProperties['certificateBehaviours'][] = $newCertificateType;
        }

        $returnValue = null;
        if ($certificateTypeId) {
            $found = false;
            foreach ($newUserProperties['certificateBehaviours'] as $key => $certificateType) {
                if ($certificateType['certificateType'] == $certificateTypeId) {
                    $found = true;
                    $returnValue = $key;
                    break;
                }
            }
            if (!$found) {
                $newCertificateType = [];
                $newCertificateType['certificateType'] = $certificateTypeId;
                if (null !== $pkiFlag) {
                    $newCertificateType[$pkiKey] = $pkiFlag;
                }
                $newUserProperties['certificateBehaviours'][] = $newCertificateType;
                $returnValue = array_search($newCertificateType, $newUserProperties['certificateBehaviours']);
            }
        }

        $this->requestEditUser($newUserProperties);

        return $returnValue;
    }

    /**
     * Method executes api call to enable user with possibility to check or uncheck generate/revoke flag (based on this properties) - using user edit.
     * Set $certificateTypeId will forcefuly add row in usableCertificates with flag. Method returns key of added $certificateTypeId.
     */
    public function enableUser(?bool $pkiFlag = null, ?int $certificateTypeId = null): ?int
    {
        $this->requestGetUser();

        $userProperties = $this->getResponseContentAsArray();
        $newUserProperties = [];

        $pkiKey = 'generateCertificate';

        $newUserProperties['certificateBehaviours'] = [];
        foreach ($userProperties['useableCertificates'] as $certificateType) {
            $newCertificateType = [];
            $newCertificateType['certificateType'] = $certificateType['certificateType']['id'];
            if (null !== $pkiFlag && $newCertificateType['certificateType'] == $this->certificateType->getId()) {
                $newCertificateType[$pkiKey] = $pkiFlag;
            }
            $newUserProperties['certificateBehaviours'][] = $newCertificateType;
        }

        $returnValue = null;
        if ($certificateTypeId) {
            $found = false;
            foreach ($newUserProperties['certificateBehaviours'] as $key => $certificateType) {
                if ($certificateType['certificateType'] == $certificateTypeId) {
                    $found = true;
                    $returnValue = $key;
                    break;
                }
            }
            if (!$found) {
                $newCertificateType = [];
                $newCertificateType['certificateType'] = $certificateTypeId;
                if (null !== $pkiFlag) {
                    $newCertificateType[$pkiKey] = $pkiFlag;
                }
                $newUserProperties['certificateBehaviours'][] = $newCertificateType;
                $returnValue = array_search($newCertificateType, $newUserProperties['certificateBehaviours']);
            }
        }

        $this->requestEnableUser($newUserProperties);

        return $returnValue;
    }

    /**
     * Method executes api call to disable user with possibility to check or uncheck generate/revoke flag (based on this properties) - using user edit.
     * Set $certificateTypeId will forcefuly add row in usableCertificates with flag. Method returns key of added $certificateTypeId.
     */
    public function disableUser(?bool $pkiFlag = null, ?int $certificateTypeId = null): ?int
    {
        $this->requestGetUser();

        $userProperties = $this->getResponseContentAsArray();
        $newUserProperties = [];

        $pkiKey = 'revokeCertificate';

        $newUserProperties['certificateBehaviours'] = [];
        foreach ($userProperties['useableCertificates'] as $certificateType) {
            $newCertificateType = [];
            $newCertificateType['certificateType'] = $certificateType['certificateType']['id'];
            if (null !== $pkiFlag && $newCertificateType['certificateType'] == $this->certificateType->getId()) {
                $newCertificateType[$pkiKey] = $pkiFlag;
            }
            $newUserProperties['certificateBehaviours'][] = $newCertificateType;
        }

        $returnValue = null;
        if ($certificateTypeId) {
            $found = false;
            foreach ($newUserProperties['certificateBehaviours'] as $key => $certificateType) {
                if ($certificateType['certificateType'] == $certificateTypeId) {
                    $found = true;
                    $returnValue = $key;
                    break;
                }
            }
            if (!$found) {
                $newCertificateType = [];
                $newCertificateType['certificateType'] = $certificateTypeId;
                if (null !== $pkiFlag) {
                    $newCertificateType[$pkiKey] = $pkiFlag;
                }
                $newUserProperties['certificateBehaviours'][] = $newCertificateType;
                $returnValue = array_search($newCertificateType, $newUserProperties['certificateBehaviours']);
            }
        }

        $this->requestDisableUser($newUserProperties);

        return $returnValue;
    }

    // just get no asserts
    public function getCertificateFromUseableCertificates(CertificateType $certificateType, array $useableCertificate): ?array
    {
        foreach ($useableCertificate as $useableCertificate) {
            if (Arr::get($useableCertificate, 'certificate.certificateType.id') === $certificateType->getId()) {
                return $useableCertificate['certificate'];
            }
        }

        return null;
    }

    public function getCertificateSubjectFromUseableCertificates(CertificateType $certificateType, array $useableCertificate): string
    {
        $certificate = $this->assertPkiHasCertificate($certificateType, $useableCertificate);
        $this->assertArrayHasKey('certificateSubject', $certificate);
        $this->assertNotEmpty($certificate['certificateSubject']);

        return $certificate['certificateSubject'];
    }

    public function getCertificateValidToFromUseableCertificates(CertificateType $certificateType, array $useableCertificate): string
    {
        $certificate = $this->assertPkiHasCertificate($certificateType, $useableCertificate);
        $this->assertArrayHasKey('certificateValidTo', $certificate);
        $this->assertNotEmpty($certificate['certificateValidTo']);

        return $certificate['certificateValidTo'];
    }

    public function assertPkiHasCertificate(CertificateType $certificateType, array $useableCertificate): array
    {
        $certificate = $this->getCertificateFromUseableCertificates($certificateType, $useableCertificate);
        $this->assertNotNull($certificate);
        $this->assertArrayHasKey('certificateGenerated', $certificate);
        $this->assertArrayHasKey('hasCertificate', $certificate);
        $this->assertTrue($certificate['certificateGenerated']);
        $this->assertTrue($certificate['hasCertificate']);

        return $certificate;
    }

    public function assertNotHasCertificate(CertificateType $certificateType, array $useableCertificate): void
    {
        $certificate = $this->getCertificateFromUseableCertificates($certificateType, $useableCertificate);
        if ($certificate) {
            if (isset($certificate['certificateGenerated'])) {
                $this->assertFalse($certificate['certificateGenerated']);
            }
            if (isset($certificate['hasCertificate'])) {
                $this->assertFalse($certificate['hasCertificate']);
            }
        }
    }

    // Method assumes no need for suffixes eg. -1, -2....
    public function assertValidCertificateSubject(Device $device, CertificateType $certificateType, array $useableCertificate): void
    {
        $certificateSubject = $this->getCertificateSubjectFromUseableCertificates($certificateType, $useableCertificate);

        $prefix = $certificateType->getCommonNamePrefix().$device->getDeviceType()->getCertificateCommonNamePrefix();

        $expectedCertificateSubject = substr(Urlizer::urlize($prefix.'-'.$device->getName()), 0, 53);

        $this->assertEquals($expectedCertificateSubject, $certificateSubject);
    }

    // Method assumes no need for suffixes eg. -1, -2....
    public function assertUserValidCertificateSubject(User $user, CertificateType $certificateType, array $useableCertificate): void
    {
        $certificateSubject = $this->getCertificateSubjectFromUseableCertificates($certificateType, $useableCertificate);

        $prefix = $certificateType->getCommonNamePrefix();

        $expectedCertificateSubject = substr(Urlizer::urlize($prefix.'-'.$user->getUsername()), 0, 53);

        $this->assertEquals($expectedCertificateSubject, $certificateSubject);
    }

    public function assertCertificateBehavioursValidationError(string $errorMessage, string|int $useableCertificateKey, string $usableCertificateField): void
    {
        $this->assertSame(400, $this->getResponseStatusCode(), 'Expected 400 response status code');

        $arrayResponse = $this->getResponseContentAsArray();

        $this->assertTrue(Arr::has($arrayResponse, 'errors.children.certificateBehaviours.children.'.$useableCertificateKey.'.children.'.$usableCertificateField.'.errors.0.message'));

        $this->assertSame($errorMessage, Arr::get($arrayResponse, 'errors.children.certificateBehaviours.children.'.$useableCertificateKey.'.children.'.$usableCertificateField.'.errors.0.message'));
    }

    public function assertDeviceTypeCertificatesValidationError(string $errorMessage, string|int $useableCertificateKey, string $usableCertificateField): void
    {
        $this->assertSame(400, $this->getResponseStatusCode(), 'Expected 400 response status code');

        $arrayResponse = $this->getResponseContentAsArray();

        $this->assertTrue(Arr::has($arrayResponse, 'errors.children.certificateTypes.children.'.$useableCertificateKey.'.children.'.$usableCertificateField.'.errors.0.message'));

        $this->assertSame($errorMessage, Arr::get($arrayResponse, 'errors.children.certificateTypes.children.'.$useableCertificateKey.'.children.'.$usableCertificateField.'.errors.0.message'));
    }
}
