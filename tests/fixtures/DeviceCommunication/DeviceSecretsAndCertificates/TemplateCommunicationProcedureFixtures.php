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

namespace Tests\DataFixtures\DeviceCommunication\DeviceSecretsAndCertificates;

use App\DataFixtures\AbstractFixtureGroupAsClass;
use App\DataFixtures as ProdFixtures;
use App\Entity\Config;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use App\Enum\SourceType;
use App\Enum\TemplateVersionType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Builder\DeviceCommunicationTestMatrixBuilder;

class TemplateCommunicationProcedureFixtures extends AbstractFixtureGroupAsClass implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (DeviceCommunicationTestMatrixBuilder::buildDeviceTypesSecretsCertificatesMatrix() as $deviceTypeConfiguration) {
            $deviceTypeName = $deviceTypeConfiguration[0]['deviceTypeName'];
            $deviceType = $this->getReference('DeviceType-'.$deviceTypeName, DeviceType::class);

            $this->createTemplateNoTemplateVersion($manager, $deviceType);

            $this->createTemplateWithTemplateVersion(
                manager: $manager,
                deviceType: $deviceType,
                templateName: 'emptyProductionTemplateVersion',
                hasProduction: true,
                hasStaging: false,
                fullProduction: false,
                fullStaging: false,
            );
            $this->createTemplateWithTemplateVersion(
                manager: $manager,
                deviceType: $deviceType,
                templateName: 'emptyStagingTemplateVersion',
                hasProduction: false,
                hasStaging: true,
                fullProduction: false,
                fullStaging: false,
            );
            $this->createTemplateWithTemplateVersion(
                manager: $manager,
                deviceType: $deviceType,
                templateName: 'emptyBothTemplateVersion',
                hasProduction: true,
                hasStaging: true,
                fullProduction: false,
                fullStaging: false,
            );
            $this->createTemplateWithTemplateVersion(
                manager: $manager,
                deviceType: $deviceType,
                templateName: 'fullProductionTemplateVersion',
                hasProduction: true,
                hasStaging: false,
                fullProduction: true,
                fullStaging: false,
            );
            $this->createTemplateWithTemplateVersion(
                manager: $manager,
                deviceType: $deviceType,
                templateName: 'fullStagingTemplateVersion',
                hasProduction: false,
                hasStaging: true,
                fullProduction: false,
                fullStaging: true,
            );
            $this->createTemplateWithTemplateVersion(
                manager: $manager,
                deviceType: $deviceType,
                templateName: 'fullBothTemplateVersion',
                hasProduction: true,
                hasStaging: true,
                fullProduction: true,
                fullStaging: true,
            );
            $this->createTemplateWithTemplateVersion(
                manager: $manager,
                deviceType: $deviceType,
                templateName: 'emptyStagingFullProductionTemplateVersion',
                hasProduction: true,
                hasStaging: true,
                fullProduction: true,
                fullStaging: false,
            );
            $this->createTemplateWithTemplateVersion(
                manager: $manager,
                deviceType: $deviceType,
                templateName: 'fullStagingEmptyProductionTemplateVersion',
                hasProduction: true,
                hasStaging: true,
                fullProduction: false,
                fullStaging: true,
            );

            $manager->flush();
        }
    }

    protected function createTemplateNoTemplateVersion(ObjectManager $manager, DeviceType $deviceType): void
    {
        $templateName = 'noTemplateVersion';

        $template = new Template();
        $template->setDeviceType($deviceType);
        $template->setName($templateName);
        $manager->persist($template);
    }

    protected function createTemplateWithTemplateVersion(ObjectManager $manager, DeviceType $deviceType, string $templateName, bool $hasProduction, bool $hasStaging, bool $fullProduction, bool $fullStaging): void
    {
        $template = new Template();
        $template->setDeviceType($deviceType);
        $template->setName($templateName);
        $manager->persist($template);
        $manager->flush();

        if ($hasProduction) {
            $productionTemplateVersion = new TemplateVersion();
            $productionTemplateVersion->setType(TemplateVersionType::PRODUCTION);
            $productionTemplateVersion->setDeviceType($deviceType);
            $productionTemplateVersion->setName($template->getName().'ProductionTemplateVersion');

            $productionTemplateVersion->setTemplate($template);

            if ($fullProduction) {
                $productionTemplateVersion->setConfig1($this->createConfig($manager, $deviceType, $templateName, Feature::PRIMARY));
                $productionTemplateVersion->setConfig2($this->createConfig($manager, $deviceType, $templateName, Feature::SECONDARY));
                $productionTemplateVersion->setConfig3($this->createConfig($manager, $deviceType, $templateName, Feature::TERTIARY));
                $productionTemplateVersion->setFirmware1($this->createFirmware($manager, $deviceType, $templateName, Feature::PRIMARY));
                $productionTemplateVersion->setFirmware2($this->createFirmware($manager, $deviceType, $templateName, Feature::SECONDARY));
                $productionTemplateVersion->setFirmware3($this->createFirmware($manager, $deviceType, $templateName, Feature::TERTIARY));
            }

            $manager->persist($productionTemplateVersion);

            $template->setProductionTemplate($productionTemplateVersion);
            $manager->persist($template);
            $manager->flush();
        }

        if ($hasStaging) {
            $stagingTemplateVersion = new TemplateVersion();
            $stagingTemplateVersion->setType(TemplateVersionType::STAGING);
            $stagingTemplateVersion->setDeviceType($deviceType);
            $stagingTemplateVersion->setName($template->getName().'StagingTemplateVersion');

            $stagingTemplateVersion->setTemplate($template);

            if ($fullStaging) {
                $stagingTemplateVersion->setConfig1($this->createConfig($manager, $deviceType, $templateName, Feature::PRIMARY));
                $stagingTemplateVersion->setConfig2($this->createConfig($manager, $deviceType, $templateName, Feature::SECONDARY));
                $stagingTemplateVersion->setConfig3($this->createConfig($manager, $deviceType, $templateName, Feature::TERTIARY));
                $stagingTemplateVersion->setFirmware1($this->createFirmware($manager, $deviceType, $templateName, Feature::PRIMARY));
                $stagingTemplateVersion->setFirmware2($this->createFirmware($manager, $deviceType, $templateName, Feature::SECONDARY));
                $stagingTemplateVersion->setFirmware3($this->createFirmware($manager, $deviceType, $templateName, Feature::TERTIARY));
            }

            $manager->persist($stagingTemplateVersion);

            $template->setStagingTemplate($stagingTemplateVersion);
            $manager->persist($template);
            $manager->flush();
        }
    }

    protected function createConfig(ObjectManager $manager, DeviceType $deviceType, string $templateName, Feature $feature): ?Config
    {
        $getHasConfig = 'getHasConfig'.$feature->value;

        if (!$deviceType->$getHasConfig()) {
            return null;
        }

        $config = new Config();
        $config->setDeviceType($deviceType);
        $config->setFeature($feature);
        $config->setName($templateName.'Config'.$feature->value);
        $config->setGenerator(ConfigGenerator::TWIG);
        $config->setUuid($this->generateUuid());
        $config->setContent($this->getConfigContent($feature, $templateName));

        $manager->persist($config);

        return $config;
    }

    protected function getConfigContent(Feature $feature, string $templateName): string
    {
        $deviceSecretPrefixes = ['renew', 'generate_renew', 'generate', 'none', 'renew_noVars', 'generate_renew_noVars', 'generate_noVars', 'none_noVars'];
        $deviceSecretSuffixes = ['Plain', 'Base64', 'CryptMd5', 'CryptBlowFish', 'CryptSha256', 'CryptSha512'];
        $certificateVariableNames = ['', 'pki', 'pk2', 'npk'];
        $certificateSuffixes = ['certificatePlain'];

        $configArray = [];
        $configArray['feature'] = $feature->name;
        $configArray['template'] = $templateName;
        foreach ($deviceSecretPrefixes as $deviceSecretPrefix) {
            foreach ($deviceSecretSuffixes as $deviceSecretSuffix) {
                $variableValue =
                '{% if '.$deviceSecretPrefix.$deviceSecretSuffix.' is defined %}{{ '.$deviceSecretPrefix.$deviceSecretSuffix.' }}{% else %}N/A{% endif %}';

                $configArray[$deviceSecretPrefix.$deviceSecretSuffix] = $variableValue;
            }
        }

        foreach ($certificateVariableNames as $certificateVariableName) {
            foreach ($certificateSuffixes as $certificateSuffix) {
                $variableValue =
                '{% if '.$certificateVariableName.$certificateSuffix.' is defined %}{{ '.$certificateVariableName.$certificateSuffix.' }}{% else %}N/A{% endif %}';

                $configArray[$certificateVariableName.$certificateSuffix] = $variableValue;
            }
        }

        return \json_encode($configArray);
    }

    protected function createFirmware(ObjectManager $manager, DeviceType $deviceType, string $templateName, Feature $feature): ?Firmware
    {
        $getHasFirmware = 'getHasFirmware'.$feature->value;

        if (!$deviceType->$getHasFirmware()) {
            return null;
        }

        $firmware = new Firmware();
        $firmware->setDeviceType($deviceType);
        $firmware->setFeature($feature);
        $firmware->setSourceType(SourceType::EXTERNAL_URL);
        $firmware->setName($templateName.'Firmware'.$feature->value);
        $firmware->setVersion('1.0');
        $firmware->setMd5(md5('abcdef1234567890'));
        $firmware->setExternalUrl('http://example.com/'.$templateName.'Firmware'.$feature->value.'.bin');
        $firmware->setUuid($this->generateUuid());
        $firmware->setSecret(substr(Uuid::v4()->toBase32(), 0, 6));

        $manager->persist($firmware);

        return $firmware;
    }

    protected function generateUuid(): string
    {
        return $uuid4 = substr(Uuid::v4()->toRfc4122(), 0, 36);
    }

    public function getDependencies(): array
    {
        return [
            ProdFixtures\UserFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
            TestFixtures\DeviceCommunication\DeviceSecretsAndCertificates\DeviceTypeCommunicationProcedureFixtures::class,
        ];
    }
}
