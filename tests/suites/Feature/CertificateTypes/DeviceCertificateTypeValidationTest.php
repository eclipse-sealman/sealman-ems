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

use App\Entity\CertificateType;
use App\Enum\CertificateBehavior;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractCertificateTypeTestCase;
use Tests\Utilities\Provider\DeviceTypeProvider;

/**
 * This class executes following test scenarios of device's certificates:
 * Actions:
 * 1. Edit
 * 2. Disable
 * 3. Enable
 * 4. Generate
 * 5. Revoke
 * Actions are tested in following setup:
 * A. Valid certificateType
 * B. Invalid certificateType - not available
 * B. Invalid certificateType - invalid CertificateEntity
 * B. Invalid certificateType - non-existent certtificateType.
 */
#[Group('full')]
#[Group('Feature')] // To be used in CI parallel tests
class DeviceCertificateTypeValidationTest extends AbstractCertificateTypeTestCase
{
    public static function getFixtureGroups(): array
    {
        return \array_merge(
            parent::getFixtureGroups(),
            [
                TestFixtures\CertificateTypes\DisableProductionCertificateTypesFixtures::class,
            ],
        );
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEditValidCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->updateDeviceEnable(true, null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertValidCertificateSubject($this->device, $this->certificateType, $arrayResponse['useableCertificates']);

        // Test automatic revocation
        $this->updateDeviceEnable(false, null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEditInvalidCertificateEntity(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Technician VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->updateDeviceEnable(true, null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEditNotAvailableCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->updateDeviceEnable(true, null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEditNotExistingCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist
        $invalidCertificateTypeKey = $this->updateDeviceEnable(true, null, time());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEnableValidCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->enableDevice(null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertValidCertificateSubject($this->device, $this->certificateType, $arrayResponse['useableCertificates']);

        // Test automatic revocation
        $this->disableDevice(null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEnableInvalidCertificateEntity(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Technician VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->device->setEnabled(false);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->enableDevice(null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEnableNotAvailableCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->device->setEnabled(false);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->enableDevice(null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEnableNotExistingCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->device->setEnabled(false);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist
        $invalidCertificateTypeKey = $this->enableDevice(null, time());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceDisableInvalidCertificateEntity(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Technician VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->device->setEnabled(true);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->disableDevice(null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceDisableNotAvailableCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->device->setEnabled(true);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->disableDevice(null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceDisableNotExistingCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->device->setEnabled(true);
        $this->getEntityManager()->persist($this->device);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist
        $invalidCertificateTypeKey = $this->disableDevice(null, time());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceGenerateInvalidCertificateEntity(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Technician VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->requestCertificateGeneration($invalidCertificateType->getId());

        $this->assertResponse404();
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceGenerateNotAvailableCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->requestCertificateGeneration($invalidCertificateType->getId());

        $this->assertResponse404();
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceGenerateNotExistingCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist

        $this->requestCertificateGeneration($invalidCertificateTypeId);

        $this->assertResponse404();
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceRevokeInvalidCertificateEntity(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Technician VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->requestCertificateRevocation($invalidCertificateType->getId());

        $this->assertResponse404();
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceRevokeNotAvailableCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->requestCertificateRevocation($invalidCertificateType->getId());

        $this->assertResponse404();
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceRevokeNotExistingCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist

        $this->requestCertificateRevocation($invalidCertificateTypeId);

        $this->assertResponse404();
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testGenerateRevokeCertificate(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->requestCertificateGeneration();

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertValidCertificateSubject($this->device, $this->certificateType, $arrayResponse['useableCertificates']);

        $this->requestCertificateRevocation();

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);
    }
}
