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

use App\DataFixtures as ProdFixtures;
use App\Entity\CertificateType;
use App\Enum\CertificateBehavior;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractCertificateTypeTestCase;
use Tests\Utilities\Provider\DeviceTypeProvider;

#[Group('full')]
#[Group('Feature')] // To be used in CI parallel tests
class DeviceTypeCertificateTypeValidationTest extends AbstractCertificateTypeTestCase
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
            TestFixtures\CertificateTypes\CertificateTypeFixtures::class,
            TestFixtures\CertificateTypes\DeviceTypeFixtures::class,
        ];
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceTypeEditEnableValidCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceTypeFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->updateDeviceType(true, $this->certificateType->getId());

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDeviceType();

        $this->assertResponseIsSuccessfulJson();
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceTypeEditInvalidCertificateEntity(string $deviceTypeName = 'TK800')
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Technician VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceTypeFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->updateDeviceType(true, $invalidCertificateType->getId());

        $this->assertDeviceTypeCertificatesValidationError('validation.deviceType.certificateCategoryNotSupported', $invalidCertificateTypeKey, 'certificateType');
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceTypeEditAddCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadDeviceTypeFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        $this->updateDeviceType(true, $invalidCertificateType->getId());

        $this->assertResponseIsSuccessfulJson();
    }

    #[DataProviderExternal(DeviceTypeProvider::class, 'getNames')]
    public function testDeviceEditNotExistingCertificateType(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceTypeFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist
        $invalidCertificateTypeKey = $this->updateDeviceType(true, $invalidCertificateTypeId);

        $this->assertDeviceTypeCertificatesValidationError('The selected choice is invalid.', $invalidCertificateTypeKey, 'certificateType');
    }
}
