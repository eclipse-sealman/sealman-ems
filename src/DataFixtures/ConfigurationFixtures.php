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

use App\Entity\Configuration;
use App\Service\Helper\VpnAddressManagerTrait;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class ConfigurationFixtures extends AbstractFixtureGroupProd implements DependentFixtureInterface
{
    use VpnAddressManagerTrait;

    public const CONFIGURATION_REFERENCE = 'configuration';

    public function load(ObjectManager $manager): void
    {
        $configuration = new Configuration();

        $configuration->setInstallationID(Uuid::v4()->toRfc4122());

        $manager->persist($configuration);

        $this->vpnAddressManager->addConfigurationSubnets($configuration);

        $manager->flush();

        $this->addReference(self::CONFIGURATION_REFERENCE, $configuration);
    }

    public function getDependencies(): array
    {
        return [
            CertificateTypeFixtures::class,
        ];
    }
}
