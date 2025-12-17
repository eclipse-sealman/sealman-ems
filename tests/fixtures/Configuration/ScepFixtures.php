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

namespace Tests\DataFixtures\Configuration;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\CertificateType;
use App\Entity\Configuration;
use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyLength;
use App\Enum\PkiType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ScepFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $configuration = $this->getReference(ProdFixtures\ConfigurationFixtures::CONFIGURATION_REFERENCE, Configuration::class);

        $deviceVpnCertificateType = $this->getReference(ProdFixtures\CertificateTypeFixtures::DEVICE_VPN_CERTIFICATE_TYPE, CertificateType::class);
        $technicianVpnCertificateType = $this->getReference(ProdFixtures\CertificateTypeFixtures::TECHNICIAN_VPN_CERTIFICATE_TYPE, CertificateType::class);
        $dpsCertificateType = $this->getReference(ProdFixtures\CertificateTypeFixtures::DPS_CERTIFICATE_TYPE, CertificateType::class);
        $edgeCaCertificateType = $this->getReference(ProdFixtures\CertificateTypeFixtures::EDGE_CA_CERTIFICATE_TYPE, CertificateType::class);

        $scepServerUrl = 'https://localhost/fake';
        $scepDevicesCaPath = 'fake-devices-openvpn-ca';
        $scepTechniciansCaPath = 'fake-technicians-openvpn-ca';
        $scepDpsCaPath = 'fake-devices-openvpn-ca';
        $scepEdgeCaCaPath = 'fake-devices-openvpn-ca';

        $this->updateCertificateType($manager, $configuration, $deviceVpnCertificateType, $scepServerUrl, $scepDevicesCaPath);
        $this->updateCertificateType($manager, $configuration, $technicianVpnCertificateType, $scepServerUrl, $scepTechniciansCaPath);
        $this->updateCertificateType($manager, $configuration, $dpsCertificateType, $scepServerUrl, $scepDpsCaPath);
        $this->updateCertificateType($manager, $configuration, $edgeCaCertificateType, $scepServerUrl, $scepEdgeCaCaPath, 'edgeca/');

        $manager->persist($configuration);
        $manager->flush();
    }

    protected function updateCertificateType(ObjectManager $manager, Configuration $configuration, CertificateType $certificateType, string $scepServerUrl, string $scepCaPath, string $scepUrlSuffix = '')
    {
        $certificateType->setScepUrl($scepServerUrl.'/scep/'.$scepUrlSuffix.$scepCaPath);
        $certificateType->setScepCrlUrl($scepServerUrl.'/scep/'.$scepCaPath.'/crl');
        $certificateType->setScepRevocationUrl($scepServerUrl.'/scep/'.$scepCaPath.'/revoke');
        $certificateType->setScepRevocationBasicAuthUser('scepuser');
        $certificateType->setScepRevocationBasicAuthPassword('sceppassword');
        $certificateType->setScepHashFunction(PkiHashAlgorithm::SHA512);
        $certificateType->setScepKeyLength(PkiKeyLength::KEY4096);
        $certificateType->setPkiType(PkiType::SCEP);
        $certificateType->setScepTimeout(900);

        $manager->persist($certificateType);
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
            ProdFixtures\ConfigurationFixtures::class,
        ];
    }
}
