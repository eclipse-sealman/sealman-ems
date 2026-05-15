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

namespace Tests\DataFixtures\HardwareFirmwares;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeHardware;
use App\Entity\Firmware;
use App\Entity\FirmwareHardwareFile;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\Feature;
use App\Enum\SourceType;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\FileManagerTrait;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tests\DataFixtures as TestFixtures;
use Tests\DataFixtures\TestFixtureTrait;

class HardwareFirmwaresFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    use DeviceCommunicationFactoryTrait;
    use FileManagerTrait;
    use TestFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        $this->provideObjectManager($manager);

        $deviceTypeNames = [
            'Edge gateway',
            'TK800',
            'TK600',
            'TK100',
            'TK500',
            'TK500v2',
            'TK500v3',
            'SG-gateway',
            'Edge gateway with VPN Container Client',
        ];

        foreach ($deviceTypeNames as $deviceTypeName) {
            $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]);
            $deviceType->setHasHardwares(true);
            $manager->persist($deviceType);

            $this->provide($deviceType);

            $this->createFirmwareDisabledHardwareFiles();
            $this->createFirmwareEnabledHardwareFiles();

            $this->flush();
        }

        $manager->flush();
    }

    protected function createFirmwareDisabledHardwareFiles()
    {
        $deviceType = $this->get(DeviceType::class);
        $features = [
            Feature::PRIMARY,
            Feature::SECONDARY,
            Feature::TERTIARY,
        ];

        $templateVersionParameters = [];

        foreach ($features as $feature) {
            if ($this->supportsFirmwareFeature($deviceType, $feature)) {
                $firmware = $this->create(Firmware::class, [
                    'name' => '{{ deviceType.name }} firmware uploaded',
                    'feature' => $feature,
                    'enableHardwareFiles' => false,
                    'sourceType' => SourceType::UPLOAD,
                ]);

                $templateVersionParameters['firmware'.$feature->value] = $firmware;
            }
        }

        $template = $this->create(Template::class);
        $this->create(TemplateVersion::class, $templateVersionParameters);
        $this->create(Device::class, [
            'template' => $template,
            'staging' => true,
        ]);

        $templateVersionParameters = [];

        foreach ($features as $feature) {
            if ($this->supportsFirmwareFeature($deviceType, $feature)) {
                $firmware = $this->create(Firmware::class, [
                    'name' => '{{ deviceType.name }} firmware uploaded',
                    'feature' => $feature,
                    'enableHardwareFiles' => false,
                    'sourceType' => SourceType::EXTERNAL_URL,
                ]);

                $templateVersionParameters['firmware'.$feature->value] = $firmware;
            }
        }

        $template = $this->create(Template::class);
        $this->create(TemplateVersion::class, $templateVersionParameters);
        $this->create(Device::class, [
            'template' => $template,
            'staging' => true,
        ]);
    }

    protected function createFirmwareEnabledHardwareFiles()
    {
        $deviceType = $this->get(DeviceType::class);
        $features = [
            Feature::PRIMARY,
            Feature::SECONDARY,
            Feature::TERTIARY,
        ];

        $templateVersionParameters = [];

        foreach ($features as $feature) {
            if ($this->supportsFirmwareFeature($deviceType, $feature)) {
                $firmware = $this->create(Firmware::class, [
                    'name' => '{{ deviceType.name }} firmware hardware files',
                    'feature' => $feature,
                    'enableHardwareFiles' => true,
                ]);
                $this->create(DeviceTypeHardware::class);
                $this->create(FirmwareHardwareFile::class, [
                    'sourceType' => SourceType::UPLOAD,
                ]);
                $this->create(DeviceTypeHardware::class);
                $this->create(FirmwareHardwareFile::class, [
                    'sourceType' => SourceType::EXTERNAL_URL,
                ]);

                $templateVersionParameters['firmware'.$feature->value] = $firmware;
            }
        }

        $template = $this->create(Template::class);
        $this->create(TemplateVersion::class, $templateVersionParameters);

        $this->create(Device::class, [
            'template' => $template,
            'staging' => true,
        ]);
    }

    protected function supportsFirmwareFeature(DeviceType $deviceType, Feature $feature): bool
    {
        switch ($feature) {
            case Feature::PRIMARY:
                $requirement = CommunicationProcedureRequirement::HAS_FIRMWARE1;
                break;
            case Feature::SECONDARY:
                $requirement = CommunicationProcedureRequirement::HAS_FIRMWARE2;
                break;
            case Feature::TERTIARY:
                $requirement = CommunicationProcedureRequirement::HAS_FIRMWARE3;
                break;
            default:
                throw new \Exception('Unsupported feature');
        }

        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
        if (!$communicationProcedure) {
            throw new \Exception('No communication procedure found');
        }

        $supported = [
            ...$communicationProcedure->getCommunicationProcedureRequirementsRequired(),
            ...$communicationProcedure->getCommunicationProcedureRequirementsOptional(),
        ];

        return in_array($requirement, $supported);
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\DeviceAuthenticationFixtures::class,
            ProdFixtures\ConfigurationFixtures::class,
            TestFixtures\Configuration\ScepFixtures::class,
            TestFixtures\Configuration\VpnFixtures::class,
            ProdFixtures\UserFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }
}
