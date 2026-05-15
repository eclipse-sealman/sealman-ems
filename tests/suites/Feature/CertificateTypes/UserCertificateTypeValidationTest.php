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
use App\Entity\User;
use App\Enum\CertificateBehavior;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\Abstract\AbstractCertificateTypeTestCase;

/**
 * This class executes following test scenarios of user's certificates:
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
class UserCertificateTypeValidationTest extends AbstractCertificateTypeTestCase
{
    #[Group('smoke')]
    public function testUserEditValidCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->updateUserEnable(true, null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetUser();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertUserValidCertificateSubject($this->user, $this->certificateType, $arrayResponse['useableCertificates']);

        // Test automatic revocation
        $this->updateUserEnable(false, null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetUser();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);
    }

    public function testUserEditInvalidCertificateEntity()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Device VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->updateUserEnable(true, null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    public function testUserEditNotExistingCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist
        $invalidCertificateTypeKey = $this->updateUserEnable(true, null, time());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    #[Group('smoke')]
    public function testUserEnableValidCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->user->setEnabled(false);
        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->enableUser(null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetUser();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertUserValidCertificateSubject($this->user, $this->certificateType, $arrayResponse['useableCertificates']);

        // Test automatic revocation
        $this->disableUser(null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetUser();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);
    }

    public function testUserEnableInvalidCertificateEntity()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Device VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->user->setEnabled(false);
        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->enableUser(null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    public function testUserEnableNotAvailableCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->user->setEnabled(false);
        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->enableUser(null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    public function testUserEnableNotExistingCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->user->setEnabled(false);
        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist
        $invalidCertificateTypeKey = $this->enableUser(null, time());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    public function testUserDisableInvalidCertificateEntity()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Device VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->user->setEnabled(true);
        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->disableUser(null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    public function testUserDisableNotAvailableCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->user->setEnabled(true);
        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeKey = $this->disableUser(null, $invalidCertificateType->getId());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    public function testUserDisableNotExistingCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->user->setEnabled(true);
        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist
        $invalidCertificateTypeKey = $this->disableUser(null, time());

        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'revokeCertificate');
        $this->assertCertificateBehavioursValidationError('validation.certificateBehavior.certificateTypeNotAvailable', $invalidCertificateTypeKey, 'generateCertificate');
    }

    public function testUserGenerateInvalidCertificateEntity()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'Device VPN']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->requestUserCertificateGeneration($invalidCertificateType->getId());

        $this->assertResponse404();
    }

    public function testUserGenerateNotExistingCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist

        $this->requestUserCertificateGeneration($invalidCertificateTypeId);

        $this->assertResponse404();
    }

    public function testUserRevokeNotAvailableCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $invalidCertificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => 'SCEP-Test-not-used']);
        $this->assertNotNull($invalidCertificateType);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $this->requestUserCertificateRevocation($invalidCertificateType->getId());

        $this->assertResponse404();
    }

    public function testUserRevokeNotExistingCertificateType()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        // Test automatic generation
        $invalidCertificateTypeId = time(); // this ID should not exist

        $this->requestUserCertificateRevocation($invalidCertificateTypeId);

        $this->assertResponse404();
    }

    public function testUserGenerateRevokeCertificate()
    {
        $this->loginApi('admin', 'admin');
        $this->user = $this->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->assertIsObject($this->user);

        $this->loadUserFromFixtures(self::SCEP_USER_TEST_CERTIFICATE_TYPE);

        $this->requestUserCertificateGeneration();

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetUser();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertUserValidCertificateSubject($this->user, $this->certificateType, $arrayResponse['useableCertificates']);

        $this->requestUserCertificateRevocation();

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetUser();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);
    }
}
