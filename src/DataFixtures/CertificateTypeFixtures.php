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

namespace App\DataFixtures;

use App\Entity\CertificateType;
use App\Enum\CertificateBehavior;
use App\Enum\CertificateCategory;
use App\Enum\CertificateEntity;
use App\Enum\PkiType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CertificateTypeFixtures extends Fixture implements FixtureGroupInterface
{
    public const DEVICE_VPN_CERTIFICATE_TYPE = 'deviceVpnCertificateType';
    public const TECHNICIAN_VPN_CERTIFICATE_TYPE = 'technicianVpnCertificateType';
    public const DPS_CERTIFICATE_TYPE = 'dpsCertificateType';
    public const EDGE_CA_CERTIFICATE_TYPE = 'edgeCaCertificateType';

    public function load(ObjectManager $manager): void
    {
        $deviceVpnCertificateType = new CertificateType();

        $deviceVpnCertificateType->setName('Device VPN');
        $deviceVpnCertificateType->setCommonNamePrefix('d');
        $deviceVpnCertificateType->setVariablePrefix('');
        $deviceVpnCertificateType->setEnabled(true);
        $deviceVpnCertificateType->setDownloadEnabled(true);
        $deviceVpnCertificateType->setUploadEnabled(true);
        $deviceVpnCertificateType->setDeleteEnabled(true);
        $deviceVpnCertificateType->setPkiEnabled(true);
        $deviceVpnCertificateType->setEnabledBehaviour(CertificateBehavior::SPECIFIC);
        $deviceVpnCertificateType->setDisabledBehaviour(CertificateBehavior::SPECIFIC);
        $deviceVpnCertificateType->setPkiType(PkiType::SCEP);
        $deviceVpnCertificateType->setCertificateCategory(CertificateCategory::DEVICE_VPN);
        $deviceVpnCertificateType->setCertificateEntity(CertificateEntity::DEVICE);
        $manager->persist($deviceVpnCertificateType);

        $technicianVpnCertificateType = new CertificateType();

        $technicianVpnCertificateType->setName('Technician VPN');
        $technicianVpnCertificateType->setCommonNamePrefix('u');
        $technicianVpnCertificateType->setVariablePrefix('');
        $technicianVpnCertificateType->setEnabled(true);
        $technicianVpnCertificateType->setDownloadEnabled(false);
        $technicianVpnCertificateType->setUploadEnabled(false);
        $technicianVpnCertificateType->setDeleteEnabled(false);
        $technicianVpnCertificateType->setPkiEnabled(true);
        $technicianVpnCertificateType->setEnabledBehaviour(CertificateBehavior::SPECIFIC);
        $technicianVpnCertificateType->setDisabledBehaviour(CertificateBehavior::SPECIFIC);
        $technicianVpnCertificateType->setPkiType(PkiType::SCEP);
        $technicianVpnCertificateType->setCertificateCategory(CertificateCategory::TECHNICIAN_VPN);
        $technicianVpnCertificateType->setCertificateEntity(CertificateEntity::USER);
        $manager->persist($technicianVpnCertificateType);

        $dpsCertificateType = new CertificateType();

        $dpsCertificateType->setName('Azure DPS');
        $dpsCertificateType->setCommonNamePrefix('a');
        $dpsCertificateType->setVariablePrefix('dps');
        $dpsCertificateType->setEnabled(true);
        $dpsCertificateType->setDownloadEnabled(true);
        $dpsCertificateType->setUploadEnabled(false);
        $dpsCertificateType->setDeleteEnabled(false);
        $dpsCertificateType->setPkiEnabled(true);
        $dpsCertificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $dpsCertificateType->setDisabledBehaviour(CertificateBehavior::ON_DEMAND);
        $dpsCertificateType->setPkiType(PkiType::SCEP);
        $dpsCertificateType->setCertificateCategory(CertificateCategory::DPS);
        $dpsCertificateType->setCertificateEntity(CertificateEntity::DEVICE);
        $manager->persist($dpsCertificateType);

        $edgeCaCertificateType = new CertificateType();

        $edgeCaCertificateType->setName('Azure Edge CA');
        $edgeCaCertificateType->setCommonNamePrefix('e');
        $edgeCaCertificateType->setVariablePrefix('egdeCa');
        $edgeCaCertificateType->setEnabled(true);
        $edgeCaCertificateType->setDownloadEnabled(true);
        $edgeCaCertificateType->setUploadEnabled(false);
        $edgeCaCertificateType->setDeleteEnabled(false);
        $edgeCaCertificateType->setPkiEnabled(true);
        $edgeCaCertificateType->setEnabledBehaviour(CertificateBehavior::AUTO);
        $edgeCaCertificateType->setDisabledBehaviour(CertificateBehavior::ON_DEMAND);
        $edgeCaCertificateType->setPkiType(PkiType::SCEP);
        $edgeCaCertificateType->setCertificateCategory(CertificateCategory::EDGE_CA);
        $edgeCaCertificateType->setCertificateEntity(CertificateEntity::DEVICE);
        $manager->persist($edgeCaCertificateType);

        $manager->flush();

        $this->addReference(self::DEVICE_VPN_CERTIFICATE_TYPE, $deviceVpnCertificateType);
        $this->addReference(self::TECHNICIAN_VPN_CERTIFICATE_TYPE, $technicianVpnCertificateType);
        $this->addReference(self::DPS_CERTIFICATE_TYPE, $dpsCertificateType);
        $this->addReference(self::EDGE_CA_CERTIFICATE_TYPE, $edgeCaCertificateType);
    }

    public static function getGroups(): array
    {
        return ['prod', 'configuration:initialize', 'test'];
    }
}
