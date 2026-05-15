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
use App\Entity\DeviceType;
use App\Entity\DeviceTypeCertificateType;
use App\Enum\CertificateEncoding;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tests\DataFixtures as TestFixtures;

class DeviceTypeFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $scepTestCertificateType = $this->getReference(CertificateTypeFixtures::SCEP_TEST_CERTIFICATE_TYPE, CertificateType::class);

        $deviceTypeNames = [
            'TK800',
            'TK500',
            'Edge gateway',
            'VPN Container Client',
            'Edge gateway with VPN Container Client',
        ];

        foreach ($deviceTypeNames as $deviceTypeName) {
            $deviceType = $manager->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]);
            $this->addDeviceTypeCertificateType($manager, $deviceType, $scepTestCertificateType);
            $manager->persist($deviceType);
        }

        $manager->flush();
    }

    protected function addDeviceTypeCertificateType(ObjectManager $manager, DeviceType $deviceType, CertificateType $certificateType, CertificateEncoding $certificateEncoding = CertificateEncoding::HEX)
    {
        $deviceTypeCertificateType = new DeviceTypeCertificateType();
        $deviceTypeCertificateType->setDeviceType($deviceType);
        $deviceTypeCertificateType->setCertificateType($certificateType);
        $deviceTypeCertificateType->setEnableCertificatesAutoRenew(true);
        $deviceTypeCertificateType->setCertificatesAutoRenewDaysBefore(14);
        $deviceTypeCertificateType->setCertificateEncoding($certificateEncoding);

        $deviceType->addCertificateType($deviceTypeCertificateType);
        $manager->persist($deviceTypeCertificateType);
    }

    public function getDependencies(): array
    {
        return [
            TestFixtures\CertificateTypes\CertificateTypeFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }
}
