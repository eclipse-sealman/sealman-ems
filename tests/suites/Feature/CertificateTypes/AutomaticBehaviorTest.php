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

use App\Enum\CertificateBehavior;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractCertificateTypeTestCase;
use Tests\Utilities\Provider\DeviceTypeProvider;

#[Group('full')]
#[Group('Feature')] // To be used in CI parallel tests
class AutomaticBehaviorTest extends AbstractCertificateTypeTestCase
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
    #[Group('smoke')]
    public function testAutomaticBehaviorAuto(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

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
    public function testAutomaticBehaviorNone(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::NONE);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::NONE);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        $this->updateDeviceEnable(true, null);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);

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
    public function testAutomaticBehaviorOnDemand(string $deviceTypeName)
    {
        $this->loginApi('admin', 'admin');

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->certificateType->setEnabledBehaviour(CertificateBehavior::ON_DEMAND);
        $this->certificateType->setDisabledBehaviour(CertificateBehavior::ON_DEMAND);
        $this->getEntityManager()->persist($this->certificateType);
        $this->getEntityManager()->flush();

        $this->loadDeviceFromFixtures(self::SCEP_TEST_CERTIFICATE_TYPE, $deviceTypeName);

        $this->updateDeviceEnable(true, false);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);

        $this->updateDeviceEnable(true, true);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertValidCertificateSubject($this->device, $this->certificateType, $arrayResponse['useableCertificates']);

        // Test automatic revocation
        $this->updateDeviceEnable(false, false);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertValidCertificateSubject($this->device, $this->certificateType, $arrayResponse['useableCertificates']);

        // Test automatic revocation
        $this->updateDeviceEnable(false, true);

        $this->assertResponseIsSuccessfulJson();

        $this->requestGetDevice();

        $this->assertResponseIsSuccessfulJson();

        $arrayResponse = $this->getResponseContentAsArray();
        $this->assertArrayHasKey('useableCertificates', $arrayResponse);

        $this->assertNotHasCertificate($this->certificateType, $arrayResponse['useableCertificates']);
    }
}
