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
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DisableProductionCertificateTypesFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // DISABLING OTHER CERTIFICATE TYPES so they not interfere with automatic behavior tests
        $productionCertificateType = $this->getReference(ProdFixtures\CertificateTypeFixtures::DEVICE_VPN_CERTIFICATE_TYPE, CertificateType::class);
        $productionCertificateType->setEnabled(false);
        $manager->persist($productionCertificateType);

        $productionCertificateType = $this->getReference(ProdFixtures\CertificateTypeFixtures::TECHNICIAN_VPN_CERTIFICATE_TYPE, CertificateType::class);
        $productionCertificateType->setEnabled(false);
        $manager->persist($productionCertificateType);

        $productionCertificateType = $this->getReference(ProdFixtures\CertificateTypeFixtures::DPS_CERTIFICATE_TYPE, CertificateType::class);
        $productionCertificateType->setEnabled(false);
        $manager->persist($productionCertificateType);

        $productionCertificateType = $this->getReference(ProdFixtures\CertificateTypeFixtures::EDGE_CA_CERTIFICATE_TYPE, CertificateType::class);
        $productionCertificateType->setEnabled(false);
        $manager->persist($productionCertificateType);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\CertificateTypeFixtures::class,
        ];
    }
}
