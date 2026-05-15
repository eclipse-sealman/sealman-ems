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

namespace Tests\DataFixtures\DeviceCommunication\DeviceSecretsAndCertificates;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\Entity\CertificateType;
use App\Enum\CertificateBehavior;
use App\Enum\CertificateCategory;
use App\Enum\CertificateEntity;
use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyType;
use App\Enum\PkiType;
use Doctrine\Persistence\ObjectManager;

class CertificateTypeCommunicationProcedureFixtures extends AbstractFixtureGroupAsClass
{
    public const PKI_CERTIFICATE_TYPE = 'pkiCertificateType';
    public const PKI_CERTIFICATE_TYPE_2 = 'pkiCertificateType2';
    public const NO_PKI_CERTIFICATE_TYPE = 'noPkiCertificateType';

    public function load(ObjectManager $manager): void
    {
        $scepServerUrl = 'https://localhost/fake';
        $scepDevicesCaPath = 'fake-devices-openvpn-ca';

        $pkiCertificateType = new CertificateType();

        $pkiCertificateType->setName('PKI certificateType');
        $pkiCertificateType->setCommonNamePrefix('pki');
        $pkiCertificateType->setVariablePrefix('pki');
        $pkiCertificateType->setEnabled(true);
        $pkiCertificateType->setDownloadEnabled(true);
        $pkiCertificateType->setUploadEnabled(true);
        $pkiCertificateType->setDeleteEnabled(true);
        $pkiCertificateType->setPkiEnabled(true);
        $pkiCertificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $pkiCertificateType->setDisabledBehaviour(CertificateBehavior::AUTO);
        $pkiCertificateType->setPkiType(PkiType::SCEP);
        $pkiCertificateType->setCertificateCategory(CertificateCategory::CUSTOM);
        $pkiCertificateType->setCertificateEntity(CertificateEntity::DEVICE);

        $manager->persist($pkiCertificateType);

        $this->updateCertificateType($manager, $pkiCertificateType, $scepServerUrl, $scepDevicesCaPath);

        $manager->flush();

        $this->addReference(self::PKI_CERTIFICATE_TYPE, $pkiCertificateType);

        $pkiCertificateType2 = new CertificateType();

        $pkiCertificateType2->setName('PKI certificateType2');
        $pkiCertificateType2->setCommonNamePrefix('pk2');
        $pkiCertificateType2->setVariablePrefix('pk2');
        $pkiCertificateType2->setEnabled(true);
        $pkiCertificateType2->setDownloadEnabled(true);
        $pkiCertificateType2->setUploadEnabled(true);
        $pkiCertificateType2->setDeleteEnabled(true);
        $pkiCertificateType2->setPkiEnabled(true);
        $pkiCertificateType2->setEnabledBehaviour(CertificateBehavior::AUTO);
        $pkiCertificateType2->setDisabledBehaviour(CertificateBehavior::AUTO);
        $pkiCertificateType2->setPkiType(PkiType::SCEP);
        $pkiCertificateType2->setCertificateCategory(CertificateCategory::CUSTOM);
        $pkiCertificateType2->setCertificateEntity(CertificateEntity::DEVICE);

        $manager->persist($pkiCertificateType2);

        $this->updateCertificateType($manager, $pkiCertificateType2, $scepServerUrl, $scepDevicesCaPath);

        $manager->flush();

        $this->addReference(self::PKI_CERTIFICATE_TYPE_2, $pkiCertificateType2);

        $noPkiCertificateType = new CertificateType();

        $noPkiCertificateType->setName('No PKI certificateType');
        $noPkiCertificateType->setCommonNamePrefix('npk');
        $noPkiCertificateType->setVariablePrefix('npk');
        $noPkiCertificateType->setEnabled(true);
        $noPkiCertificateType->setDownloadEnabled(true);
        $noPkiCertificateType->setUploadEnabled(true);
        $noPkiCertificateType->setDeleteEnabled(true);
        $noPkiCertificateType->setPkiEnabled(false);
        $noPkiCertificateType->setEnabledBehaviour(CertificateBehavior::NONE);
        $noPkiCertificateType->setDisabledBehaviour(CertificateBehavior::NONE);
        $noPkiCertificateType->setPkiType(PkiType::NONE);
        $noPkiCertificateType->setCertificateCategory(CertificateCategory::CUSTOM);
        $noPkiCertificateType->setCertificateEntity(CertificateEntity::DEVICE);

        $manager->persist($noPkiCertificateType);

        $manager->flush();

        $this->addReference(self::NO_PKI_CERTIFICATE_TYPE, $noPkiCertificateType);
    }

    protected function updateCertificateType(ObjectManager $manager, CertificateType $certificateType, string $scepServerUrl, string $scepCaPath)
    {
        $certificateType->setScepUrl($scepServerUrl.'/scep/'.$scepCaPath);
        $certificateType->setScepCrlUrl($scepServerUrl.'/scep/'.$scepCaPath.'/crl');
        $certificateType->setScepRevocationUrl($scepServerUrl.'/scep/'.$scepCaPath.'/revoke');
        $certificateType->setScepRevocationBasicAuthUser('scepuser');
        $certificateType->setScepRevocationBasicAuthPassword('sceppassword');
        $certificateType->setScepHashFunction(PkiHashAlgorithm::SHA512);
        $certificateType->setScepKeyType(PkiKeyType::RSA4096);
        $certificateType->setPkiType(PkiType::SCEP);
        $certificateType->setScepTimeout(900);

        $manager->persist($certificateType);
    }
}
