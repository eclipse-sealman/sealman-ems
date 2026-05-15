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

namespace Tests\DataFixtures\WebApi\VariableType;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\Config;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tests\DataFixtures\TestFixtureTrait;

class ConfigValueTypeTestFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use TestFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        $this->provideObjectManager($manager);

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);

        $this->provide($deviceType);

        $template = $this->create(Template::class);
        $config = $this->create(Config::class);

        $this->create(TemplateVersion::class, [
            'config1' => $config,
        ]);
        $this->create(Device::class, [
            'template' => $template,
            'staging' => true,
        ]);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }
}
