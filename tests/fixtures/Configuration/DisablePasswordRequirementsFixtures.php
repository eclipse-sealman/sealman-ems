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
use App\Entity\Configuration;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DisablePasswordRequirementsFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $configuration = $this->getReference(ProdFixtures\ConfigurationFixtures::CONFIGURATION_REFERENCE, Configuration::class);

        $configuration->setPasswordMinimumLength(1);
        $configuration->setPasswordDigitRequired(false);
        $configuration->setPasswordBigSmallCharRequired(false);
        $configuration->setPasswordSpecialCharRequired(false);

        $manager->persist($configuration);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
        ];
    }
}
