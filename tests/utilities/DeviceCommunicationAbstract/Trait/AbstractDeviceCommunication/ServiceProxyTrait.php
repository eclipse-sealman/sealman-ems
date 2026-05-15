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

namespace Tests\Utilities\DeviceCommunicationAbstract\Trait\AbstractDeviceCommunication;

use App\DeviceCommunication\DeviceCommunicationFactory;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Service\CertificateManager;
use App\Service\DeviceSecretManager;
use App\Service\EncryptionManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;

/**
 * Code separated for readability.
 * To be used only with AbstractTestCase class.
 */
trait ServiceProxyTrait
{
    /**
     * Get a required entity parameter from the API client context.
     * Throws exception if missing.
     */
    public function getRequiredProvidedEntityParameter(string $entityClass, string $parameterName): mixed
    {
        return $this->getApiClient()->getRequiredProvidedEntityParameter($entityClass, $parameterName);
    }

    /**
     * Get an entity parameter from the API client context.
     */
    public function getProvidedEntityParameter(string $entityClass, string $parameterName): mixed
    {
        return $this->getApiClient()->getProvidedEntityParameter($entityClass, $parameterName);
    }

    /**
     * Check if an entity parameter exists in the API client context.
     */
    public function hasProvidedEntityParameter(string $entityClass, string $parameterName): bool
    {
        return $this->getApiClient()->hasProvidedEntityParameter($entityClass, $parameterName);
    }

    /**
     * Get the provided device entity from the test context.
     */
    public function getProvidedDeviceEntity(): Device
    {
        $deviceId = $this->getRequiredProvidedEntityParameter(DeviceModel::class, 'id');

        $device = $this->getRepository(Device::class)->find($deviceId);

        if (null === $device) {
            throw new \Exception('Device object is not set in the provided entity class');
        }

        return $device;
    }

    /**
     * Get the provided device type entity from the test context.
     */
    public function getProvidedDeviceTypeEntity(): DeviceType
    {
        $deviceTypeId = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'id');

        $deviceType = $this->getRepository(DeviceType::class)->find($deviceTypeId);

        if (null === $deviceType) {
            throw new \Exception('DeviceType object is not set in the provided entity class');
        }

        return $deviceType;
    }

    public function getDeviceSecretManager(): DeviceSecretManager
    {
        return $this->getService(DeviceSecretManager::class);
    }

    public function getCertificateManager(): CertificateManager
    {
        return $this->getService(CertificateManager::class);
    }

    public function getEncryptionManager(): EncryptionManager
    {
        return $this->getService(EncryptionManager::class);
    }

    public function getCertificateTypeManager(): CertificateManager
    {
        return $this->getService(CertificateManager::class);
    }

    public function getDeviceCommunicationFactory(): DeviceCommunicationFactory
    {
        return $this->getService(DeviceCommunicationFactory::class);
    }

    public function getUserPasswordHasher(): UserPasswordHasherInterface
    {
        return $this->getService(UserPasswordHasherInterface::class);
    }
}
