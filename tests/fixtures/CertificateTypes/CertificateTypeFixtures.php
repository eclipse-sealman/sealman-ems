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

namespace Tests\DataFixtures\CertificateTypes;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\CertificateType;
use App\Enum\CertificateBehavior;
use App\Enum\CertificateCategory;
use App\Enum\CertificateEntity;
use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyType;
use App\Enum\PkiType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CertificateTypeFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    public const SCEP_TEST_CERTIFICATE_TYPE = 'scepTestCertificateType';
    public const SCEP_TEST_NOT_USED_CERTIFICATE_TYPE = 'scepNotUsedTestCertificateType';
    public const SCEP_USER_TEST_CERTIFICATE_TYPE = 'scepNotUsedUserTestCertificateType';

    public function load(ObjectManager $manager): void
    {
        $scepTestCertificateType = new CertificateType();

        $scepTestCertificateType->setName('SCEP-Test');
        $scepTestCertificateType->setCommonNamePrefix('dsc');
        $scepTestCertificateType->setVariablePrefix('scep');
        $scepTestCertificateType->setEnabled(true);
        $scepTestCertificateType->setDownloadEnabled(true);
        $scepTestCertificateType->setUploadEnabled(true);
        $scepTestCertificateType->setDeleteEnabled(true);
        $scepTestCertificateType->setPkiEnabled(true);
        $scepTestCertificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $scepTestCertificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $scepTestCertificateType->setPkiType(PkiType::SCEP);
        $scepTestCertificateType->setCertificateCategory(CertificateCategory::CUSTOM);
        $scepTestCertificateType->setCertificateEntity(CertificateEntity::DEVICE);
        $scepTestCertificateType->setScepTimeout(90);
        $manager->persist($scepTestCertificateType);

        $scepServerUrl = 'https://localhost/fake';
        $scepDevicesCaPath = 'fake-devices-openvpn-ca';

        $this->updateCertificateType($manager, $scepTestCertificateType, $scepServerUrl, $scepDevicesCaPath);

        $manager->flush();

        $this->addReference(self::SCEP_TEST_CERTIFICATE_TYPE, $scepTestCertificateType);

        $scepTestCertificateTypeNotUsed = new CertificateType();

        $scepTestCertificateTypeNotUsed->setName('SCEP-Test-not-used');
        $scepTestCertificateTypeNotUsed->setCommonNamePrefix('dst');
        $scepTestCertificateTypeNotUsed->setVariablePrefix('scept');
        $scepTestCertificateTypeNotUsed->setEnabled(true);
        $scepTestCertificateTypeNotUsed->setDownloadEnabled(true);
        $scepTestCertificateTypeNotUsed->setUploadEnabled(true);
        $scepTestCertificateTypeNotUsed->setDeleteEnabled(true);
        $scepTestCertificateTypeNotUsed->setPkiEnabled(true);
        $scepTestCertificateTypeNotUsed->setEnabledBehaviour(CertificateBehavior::AUTO);
        $scepTestCertificateTypeNotUsed->setDisabledBehaviour(CertificateBehavior::AUTO);
        $scepTestCertificateTypeNotUsed->setPkiType(PkiType::SCEP);
        $scepTestCertificateTypeNotUsed->setCertificateCategory(CertificateCategory::CUSTOM);
        $scepTestCertificateTypeNotUsed->setCertificateEntity(CertificateEntity::DEVICE);
        $scepTestCertificateTypeNotUsed->setScepTimeout(90);
        $manager->persist($scepTestCertificateTypeNotUsed);

        $scepServerUrl = 'https://localhost/fake';
        $scepDevicesCaPath = 'fake-devices-openvpn-ca';

        $this->updateCertificateType($manager, $scepTestCertificateTypeNotUsed, $scepServerUrl, $scepDevicesCaPath);

        $manager->flush();

        $this->addReference(self::SCEP_TEST_NOT_USED_CERTIFICATE_TYPE, $scepTestCertificateTypeNotUsed);

        $scepUserTestCertificateType = new CertificateType();

        $scepUserTestCertificateType->setName('SCEP-User-Test');
        $scepUserTestCertificateType->setCommonNamePrefix('usc');
        $scepUserTestCertificateType->setVariablePrefix('scepu');
        $scepUserTestCertificateType->setEnabled(true);
        $scepUserTestCertificateType->setDownloadEnabled(true);
        $scepUserTestCertificateType->setUploadEnabled(true);
        $scepUserTestCertificateType->setDeleteEnabled(true);
        $scepUserTestCertificateType->setPkiEnabled(true);
        $scepUserTestCertificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $scepUserTestCertificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $scepUserTestCertificateType->setPkiType(PkiType::SCEP);
        $scepUserTestCertificateType->setCertificateCategory(CertificateCategory::CUSTOM);
        $scepUserTestCertificateType->setCertificateEntity(CertificateEntity::USER);
        $scepUserTestCertificateType->setScepTimeout(90);
        $manager->persist($scepUserTestCertificateType);

        $scepServerUrl = 'https://localhost/fake';
        $scepDevicesCaPath = 'fake-devices-openvpn-ca';

        $this->updateCertificateType($manager, $scepUserTestCertificateType, $scepServerUrl, $scepDevicesCaPath);

        $manager->flush();

        $this->addReference(self::SCEP_USER_TEST_CERTIFICATE_TYPE, $scepUserTestCertificateType);
    }

    protected function updateCertificateType(ObjectManager $manager, CertificateType $certificateType, string $scepServerUrl, string $scepCaPath, string $scepUrlSuffix = '')
    {
        $certificateType->setScepUrl($scepServerUrl.'/scep/'.$scepUrlSuffix.$scepCaPath);
        $certificateType->setScepCrlUrl($scepServerUrl.'/scep/'.$scepCaPath.'/crl');
        $certificateType->setScepRevocationUrl($scepServerUrl.'/scep/'.$scepCaPath.'/revoke');
        $certificateType->setScepRevocationBasicAuthUser('scepuser');
        $certificateType->setScepRevocationBasicAuthPassword('sceppassword');
        $certificateType->setScepHashFunction(PkiHashAlgorithm::SHA512);
        $certificateType->setScepKeyType(PkiKeyType::RSA4096);
        $certificateType->setPkiType(PkiType::SCEP);
        $certificateType->setScepTimeout(90);

        $manager->persist($certificateType);
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
        ];
    }
}
