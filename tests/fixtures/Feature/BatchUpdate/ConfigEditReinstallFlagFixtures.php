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

namespace Tests\DataFixtures\Feature\BatchUpdate;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\Config;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Enum\TemplateVersionType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tests\DataFixtures\TestFixtureTrait;

class ConfigEditReinstallFlagFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use TestFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        $this->provideObjectManager($manager);

        $tk800DeviceType = $this->getReference(ProdFixtures\DeviceTypeFixtures::DEVICETYPE_ROUTERTK800_REFERENCE, DeviceType::class);
        $this->provide($tk800DeviceType);

        $config = $this->create(Config::class, [
            'name' => 'Config',
        ]);

        $template1 = $this->create(Template::class);
        $templateVersion1 = $this->create(TemplateVersion::class, [
            'template' => $template1,
            'type' => TemplateVersionType::STAGING,
            'config1' => $config,
        ]);

        $this->create(Device::class, [
            'name' => 'Device A with template, staging, flag = false',
            'template' => $template1,
            'staging' => true,
            'reinstallConfig1' => false,
        ]);
        $this->create(Device::class, [
            'name' => 'Device B with template, staging, flag = false',
            'template' => $template1,
            'staging' => true,
            'reinstallConfig1' => false,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template, staging, flag = true',
            'template' => $template1,
            'staging' => true,
            'reinstallConfig1' => true,
        ]);

        $this->create(Device::class, [
            'name' => 'Device without template, staging, flag = false',
            'reinstallConfig1' => false,
            'staging' => true,
        ]);
        $this->create(Device::class, [
            'name' => 'Device without template, staging, flag = true',
            'reinstallConfig1' => true,
            'staging' => true,
        ]);

        $this->create(Device::class, [
            'name' => 'Device without template, production, flag = false',
            'reinstallConfig1' => false,
        ]);
        $this->create(Device::class, [
            'name' => 'Device without template, production, flag = true',
            'reinstallConfig1' => true,
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
