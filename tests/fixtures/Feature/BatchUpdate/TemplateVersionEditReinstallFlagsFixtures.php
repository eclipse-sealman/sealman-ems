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
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Enum\TemplateVersionType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tests\DataFixtures\TestFixtureTrait;

class TemplateVersionEditReinstallFlagsFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use TestFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        $this->provideObjectManager($manager);

        $tk800DeviceType = $this->getReference(ProdFixtures\DeviceTypeFixtures::DEVICETYPE_ROUTERTK800_REFERENCE, DeviceType::class);
        $this->provide($tk800DeviceType);

        // 1. Template versions staging unselected and production unselected. Devices NOT connected with template.
        $template1 = $this->create(Template::class);
        $this->create(TemplateVersion::class, [
            'name' => 'Template 1 staging unselected',
            'template' => $template1,
            'type' => TemplateVersionType::STAGING,
            'select' => false,
        ]);
        $this->create(TemplateVersion::class, [
            'name' => 'Template 1 production unselected',
            'template' => $template1,
            'type' => TemplateVersionType::PRODUCTION,
            'select' => false,
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

        // 2. Template versions staging unselected, production unselected, staging selected, production selected. Devices connected with template.
        $template2 = $this->create(Template::class);
        $this->create(TemplateVersion::class, [
            'name' => 'Template 2 staging unselected',
            'template' => $template2,
            'type' => TemplateVersionType::STAGING,
            'select' => false,
        ]);
        $this->create(TemplateVersion::class, [
            'name' => 'Template 2 staging selected',
            'template' => $template2,
            'type' => TemplateVersionType::STAGING,
        ]);
        $this->create(TemplateVersion::class, [
            'name' => 'Template 2 production unselected',
            'template' => $template2,
            'type' => TemplateVersionType::PRODUCTION,
            'select' => false,
        ]);
        $this->create(TemplateVersion::class, [
            'name' => 'Template 2 production selected',
            'template' => $template2,
            'type' => TemplateVersionType::PRODUCTION,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template 2, staging, flag = false',
            'template' => $template2,
            'reinstallConfig1' => false,
            'staging' => true,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template 2, staging, flag = true',
            'template' => $template2,
            'reinstallConfig1' => true,
            'staging' => true,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template 2, production, flag = false',
            'template' => $template2,
            'reinstallConfig1' => false,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template 2, production, flag = true',
            'template' => $template2,
            'reinstallConfig1' => true,
        ]);

        // 3. Template versions staging unselected and production unselected. Devices connected with template.
        $template3 = $this->create(Template::class);
        $this->create(TemplateVersion::class, [
            'name' => 'Template 3 staging unselected',
            'template' => $template3,
            'type' => TemplateVersionType::STAGING,
            'select' => false,
        ]);
        $this->create(TemplateVersion::class, [
            'name' => 'Template 3 production unselected',
            'template' => $template3,
            'type' => TemplateVersionType::PRODUCTION,
            'select' => false,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template 3, staging, flag = false',
            'template' => $template3,
            'reinstallConfig1' => false,
            'staging' => true,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template 3, staging, flag = true',
            'template' => $template3,
            'reinstallConfig1' => true,
            'staging' => true,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template 3, production, flag = false',
            'template' => $template3,
            'reinstallConfig1' => false,
        ]);
        $this->create(Device::class, [
            'name' => 'Device with template 3, production, flag = true',
            'template' => $template3,
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
