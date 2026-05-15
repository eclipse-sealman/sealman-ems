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

namespace Tests\DataFixtures;

use App\Entity\Config;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeHardware;
use App\Entity\Firmware;
use App\Entity\FirmwareHardwareFile;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use App\Enum\SourceType;
use App\Enum\TemplateVersionType;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\FileManagerTrait;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * WIP. Intention is to provide a standardized way to create test fixtures (firmwares, templates, device etc.).
 *
 * Following code is not yet thought through and will probably be changed.
 *
 * Parameters can use following variables:
 * - {{ counter }} - will be replaced with the current counter value
 * - {{ deviceType.name }} - will be replaced with the current device type name
 * - {{ deviceType.id }} - will be replaced with the current device type ID
 */
trait TestFixtureTrait
{
    use FileManagerTrait;
    use DeviceCommunicationFactoryTrait;

    protected ?ObjectManager $manager = null;

    protected ?PropertyAccessorInterface $propertyAccessor = null;

    protected array $provides = [];

    protected array $counters = [];

    protected function create(string $entityClass, array $parameters = []): object
    {
        $this->incrementCounter($entityClass);

        $reflectionClass = new \ReflectionClass($entityClass);
        $createMethod = 'create'.$reflectionClass->getShortName();

        if (\method_exists($this, $createMethod)) {
            $entity = $this->$createMethod($parameters);
        } else {
            $entity = new $entityClass();
            $this->applyParameters($entity, $parameters);
        }

        $this->persist($entity);
        $this->provide($entity);

        return $entity;
    }

    protected function createTemplate(array $parameters = []): Template
    {
        $entity = new Template();

        $parameters['name'] ??= 'Template {{ counter }}';
        $parameters['deviceType'] ??= $this->get(DeviceType::class);

        $this->applyParameters($entity, $parameters);

        return $entity;
    }

    /**
     * Supports additional parameters:
     * - 'select' will assign template version as staging or production depending on 'type'.
     */
    protected function createTemplateVersion(array $parameters = []): TemplateVersion
    {
        $entity = new TemplateVersion();

        $template = $parameters['template'] ??= $this->get(Template::class);

        $select = $parameters['select'] ?? true;
        unset($parameters['select']);

        $parameters['name'] ??= 'Template version {{ counter }}';
        $parameters['deviceType'] ??= $template->getDeviceType();
        $parameters['type'] ??= TemplateVersionType::STAGING;

        $this->applyParameters($entity, $parameters);

        if ($select) {
            switch ($parameters['type']) {
                case TemplateVersionType::PRODUCTION:
                    $template->setProductionTemplate($entity);
                    $this->persist($template);
                    break;
                case TemplateVersionType::STAGING:
                    $template->setStagingTemplate($entity);
                    $this->persist($template);
                    break;
                default:
                    throw new \Exception('Invalid template version type');
            }
        }

        $template->getTemplateVersions()->add($entity);

        return $entity;
    }

    protected function createDevice(array $parameters = []): Device
    {
        $entity = new Device();

        $deviceType = $parameters['deviceType'] ??= $this->get(DeviceType::class);
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
        if (!$communicationProcedure) {
            throw new \Exception('No communication procedure found');
        }

        $parameters['deviceType'] ??= $this->get(DeviceType::class);
        $parameters['name'] ??= 'Device {{ counter }}';
        $parameters['serialNumber'] ??= $parameters['name'];
        $parameters['enabled'] ??= true;
        $parameters['uuid'] ??= $communicationProcedure->getDeviceTypeUniqueUuid();
        $parameters['hashIdentifier'] ??= $communicationProcedure->getDeviceUniqueHashIdentifier();
        $parameters['virtualSubnetCidr'] ??= $deviceType->getVirtualSubnetCidr();
        $parameters['masqueradeType'] ??= $deviceType->getMasqueradeType();

        $this->applyParameters($entity, $parameters);

        if (!$entity->getIdentifier()) {
            // Generating identifier may require device to be filled. Run it after applyParameters
            $entity->setIdentifier($communicationProcedure->generateIdentifier($entity));
        }

        return $entity;
    }

    protected function createDeviceTypeHardware(array $parameters = []): DeviceTypeHardware
    {
        $entity = new DeviceTypeHardware();

        $deviceType = $parameters['deviceType'] ??= $this->get(DeviceType::class);
        $parameters['name'] ??= 'Hardware {{ counter }}';
        $parameters['hardwareVersion'] ??= '{{ counter }}.0.0';

        $this->applyParameters($entity, $parameters);

        $deviceType->getDeviceTypeHardwares()->add($entity);

        return $entity;
    }

    protected function createConfig(array $parameters = []): Config
    {
        $entity = new Config();

        $parameters['deviceType'] ??= $this->get(DeviceType::class);
        $parameters['name'] ??= 'Config {{ counter }}';
        $parameters['feature'] ??= Feature::PRIMARY;
        $parameters['generator'] ??= ConfigGenerator::TWIG;
        $parameters['content'] ??= 'Config content {{ counter }}';
        $parameters['uuid'] ??= '{{ uuid }}';

        $this->applyParameters($entity, $parameters);

        return $entity;
    }

    protected function createFirmware(array $parameters = []): Firmware
    {
        $entity = new Firmware();

        $deviceType = $parameters['deviceType'] ??= $this->get(DeviceType::class);
        $parameters['enableHardwareFiles'] ??= true;
        $parameters['feature'] ??= Feature::PRIMARY;
        $parameters['secret'] ??= substr(Uuid::v4()->toBase32(), 0, 6);
        $parameters['uuid'] ??= substr(Uuid::v4()->toBase32(), 0, 6);
        $parameters['name'] ??= 'Firmware {{ counter }}';
        $version = $parameters['version'] ??= '{{ counter }}.0.0';

        if (!$parameters['enableHardwareFiles']) {
            // Device type and UUID needs to be assigned to get a valid upload dir
            $entity->setDeviceType($deviceType);
            $entity->setUuid($parameters['uuid']);
            // $version may include parameters. We need them replaced before copying files below
            $fileName = $this->normalizeParameterValue('panda-jpg-v'.$version.'.bin', $entity, $parameters);
            $parameters['sourceType'] ??= SourceType::EXTERNAL_URL;

            switch ($parameters['sourceType']) {
                case SourceType::UPLOAD:
                    $filePath = $entity->getUploadDir('filepath');

                    if (!isset($parameters['filename']) && !isset($parameters['filePath'])) {
                        // Copy mock firmware file which is actually jpg file with a panda.
                        $fs = new Filesystem();
                        $moveFilePath = str_replace('../private/', 'private/', $filePath);
                        $this->fileManager->move('src/DataFixtures/Tests/Performance/firmware.jpg', $moveFilePath, true);
                        $fs->rename($moveFilePath.'firmware.jpg', $moveFilePath.$fileName);
                    }

                    $parameters['filename'] ??= $fileName;
                    $parameters['filePath'] ??= $filePath;
                    $parameters['md5'] ??= 'b4ec0e13e68c5943aa891b93a2e9454a';
                    break;
                case SourceType::EXTERNAL_URL:
                    $parameters['externalUrl'] ??= 'https://localhost/'.$fileName;
                    $parameters['filename'] ??= basename($parameters['externalUrl']);
                    $parameters['md5'] ??= 'unknown';
                    break;
                default:
                    throw new \Exception('Invalid source type');
            }
        }

        $this->applyParameters($entity, $parameters);

        return $entity;
    }

    protected function createFirmwareHardwareFile(array $parameters = []): FirmwareHardwareFile
    {
        $entity = new FirmwareHardwareFile();

        $firmware = $parameters['firmware'] ??= $this->get(Firmware::class);
        $hardware = $parameters['hardware'] ??= $this->get(DeviceTypeHardware::class);
        $parameters['sourceType'] ??= SourceType::EXTERNAL_URL;

        // Firmware needs to be assigned to get a valid upload dir
        $entity->setFirmware($firmware);
        $fileName = 'panda-jpg-'.$hardware->getHardwareVersion().'-v'.$firmware->getVersion().'.bin';

        switch ($parameters['sourceType']) {
            case SourceType::UPLOAD:
                $filePath = $entity->getUploadDir('filepath');

                if (!isset($parameters['filename']) && !isset($parameters['filePath'])) {
                    // Copy mock firmware file which is actually jpg file with a panda.
                    $fs = new Filesystem();
                    $moveFilePath = str_replace('../private/', 'private/', $filePath);
                    $this->fileManager->move('src/DataFixtures/Tests/Performance/firmware.jpg', $moveFilePath, true);
                    $fs->rename($moveFilePath.'firmware.jpg', $moveFilePath.$fileName);
                }

                $parameters['filename'] ??= $fileName;
                $parameters['filePath'] ??= $filePath;
                $parameters['md5'] ??= 'b4ec0e13e68c5943aa891b93a2e9454a';
                break;
            case SourceType::EXTERNAL_URL:
                $parameters['externalUrl'] ??= 'https://localhost/'.$fileName;
                $parameters['filename'] ??= basename($parameters['externalUrl']);
                $parameters['md5'] ??= 'unknown';
                break;
            default:
                throw new \Exception('Invalid source type');
        }

        $this->applyParameters($entity, $parameters);

        $firmware->getHardwareFiles()->add($entity);
        $hardware->getFirmwareHardwareFiles()->add($entity);

        return $entity;
    }

    protected function applyParameters(object $entity, array $parameters = []): void
    {
        foreach ($parameters as $path => $value) {
            $this->getPropertyAccessor()->setValue($entity, $path, $this->normalizeParameterValue($value, $entity, $parameters));
        }
    }

    protected function normalizeParameterValue($value, object $entity, array $parameters = [])
    {
        if (!is_string($value)) {
            return $value;
        }

        $searches = [
            '{{ counter }}',
        ];
        $replacements = [
            $this->getCounter(get_class($entity)),
        ];

        $deviceType = $parameters['deviceType'] ?? null;
        if ($deviceType) {
            $searches[] = '{{ deviceType.name }}';
            $replacements[] = $deviceType->getName();

            $searches[] = '{{ deviceType.id }}';
            $replacements[] = $deviceType->getId();
        }

        return str_replace($searches, $replacements, $value);
    }

    protected function provide($entity): void
    {
        $this->provides[get_class($entity)] = $entity;
    }

    protected function clear($entityClass): void
    {
        unset($this->provides[$entityClass]);
    }

    protected function get(string $entityClass, bool $required = true): ?object
    {
        if (!isset($this->provides[$entityClass])) {
            if ($required) {
                throw new \Exception(sprintf('Entity %s not provided', $entityClass));
            }

            return null;
        }

        return $this->provides[$entityClass];
    }

    protected function incrementCounter(string $entityClass): void
    {
        $counter = $this->counters[$entityClass] ?? 0;
        $this->counters[$entityClass] = ++$counter;
    }

    protected function clearCounter(string $entityClass): void
    {
        $this->counters[$entityClass] = 1;
    }

    protected function getCounter(string $entityClass): int
    {
        return $this->counters[$entityClass] ?? 1;
    }

    /**
     * Provides the ObjectManager to the fixture to avoid passing it everywhere.
     *
     * Run this at the beginning of the load() method.
     *
     * $this->provideObjectManager($manager);
     */
    protected function provideObjectManager(ObjectManager $manager): void
    {
        $this->manager = $manager;
    }

    protected function getRepository($entity): EntityRepository
    {
        return $this->manager->getRepository($entity);
    }

    protected function persist($entity): void
    {
        $this->manager->persist($entity);
    }

    protected function flush(): void
    {
        $this->manager->flush();
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
