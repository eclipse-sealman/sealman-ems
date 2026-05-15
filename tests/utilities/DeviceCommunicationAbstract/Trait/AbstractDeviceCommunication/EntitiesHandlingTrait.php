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

use App\Entity\Certificate;
use App\Entity\Device;
use App\Entity\DeviceSecret;
use App\Entity\DeviceType;
use App\Entity\Template;
use App\Enum\CommunicationProcedure;
use App\Enum\PkiType;
use Carve\ApiBundle\Helper\Arr;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceStateModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;

/**
 * Code separated for readability.
 * To be used only with AbstractTestCase class.
 */
trait EntitiesHandlingTrait
{
    /**
     * Get the array key of the reinstall flag to execute with a full template.
     *
     * If all reinstall flags are false, returns default flag or null.
     * Assumes firmware version has not changed; only reinstallFirmware can force reinstall.
     */
    public function getExpectedProcessedReinstallFlag(array $reinstallFlagsConfiguration): ?string
    {
        // This code is specific to current state of communication procedure and should be updated if procedure changes
        $deviceType = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceType');
        $communicationProcedure = $deviceType->getCommunicationProcedure();

        // Vpn Container Client allways returns config1
        if (CommunicationProcedure::VPNCONTAINERCLIENT === $communicationProcedure) {
            return 'reinstallConfig1';
        }

        // Most communication procedures works with firmware1 and config1 (using regular endpoint)
        if ($reinstallFlagsConfiguration['reinstallFirmware1']) {
            return 'reinstallFirmware1';
        }

        if ($reinstallFlagsConfiguration['reinstallConfig1']) {
            return 'reinstallConfig1';
        }

        if (CommunicationProcedure::ROUTER_ONE_CONFIG === $communicationProcedure) {
            // Router communication procedures with one config will always return config1 when it is different then one from request (no config is send in request - so is always different)
            return 'reinstallConfig1';
        }

        // Handling secondary reinstall config for router communication procedures for tk800 and tk600
        if (CommunicationProcedure::ROUTER === $communicationProcedure || CommunicationProcedure::ROUTER_DSA === $communicationProcedure) {
            if ($reinstallFlagsConfiguration['reinstallConfig2']) {
                return 'reinstallConfig2';
            }
            if ($deviceType->getHasAlwaysReinstallConfig2()) {
                return 'reinstallConfig2';
            }

            // Adding return here to point out that this happens for router communication procedures with 2 configs
            // In current implementation is obsolete, but it should limit errors in future
            return null; // Empty response
        }

        return null; // Empty response
    }

    /**
     * Get expected reinstall flags configuration after processing.
     */
    public function getExpectedReinstallFlagsConfiguration(array $reinstallFlagsConfiguration, ?string $expectedProcessedReinstallFlag): array
    {
        if (null === $expectedProcessedReinstallFlag) {
            return $reinstallFlagsConfiguration;
        }

        $updatedFlag = [];
        $updatedFlag[$expectedProcessedReinstallFlag] = false;

        // Using array_merge to clone the array
        return array_merge([], $reinstallFlagsConfiguration, $updatedFlag);
    }

    /**
     * Mark all device secrets as outdated for the provided device.
     */
    public function makeDeviceSecretsOutdates(): void
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $device = $this->getProvidedDeviceEntity();

        foreach ($this->getDeviceSecretEntities($device) as $deviceSecret) {
            // Setting device secret to outdate state
            $deviceSecret->setRenewedAt((new \DateTime())->modify('-14 days'));
            $this->getEntityManager()->persist($deviceSecret);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Mark all device certificates as outdated for the provided device.
     */
    public function makeDeviceCertificatesOutdates(): void
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $device = $this->getProvidedDeviceEntity();

        foreach ($this->getDeviceCertificateEntities($device) as $deviceCertificate) {
            // Setting device certificate to outdate state
            $deviceCertificate->setCertificateValidTo((new \DateTime())->modify('1 days'));
            $this->getEntityManager()->persist($deviceCertificate);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Generate all missing device secrets for the provided device.
     */
    public function generateAllDeviceSecrets(): void
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $device = $this->getProvidedDeviceEntity();
        $deviceSecrets = $this->getDeviceSecretEntities($device);

        foreach ($device->getDeviceType()->getDeviceTypeSecrets() as $deviceTypeSecret) {
            $found = false;
            foreach ($deviceSecrets as $deviceSecret) {
                if ($deviceSecret->getDeviceTypeSecret()->getId() == $deviceTypeSecret->getId()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $deviceSecret = new DeviceSecret();
                $deviceSecret->setDevice($device);
                $deviceSecret->setDeviceTypeSecret($deviceTypeSecret);
                $deviceSecret->setRenewedAt(new \DateTime());
                $deviceSecret->setForceRenewal(false);

                $secretValue = $this->getDeviceSecretManager()->generateRandomSecret($deviceSecret);
                $deviceSecret->setSecretValue($secretValue);
                $this->getDeviceSecretManager()->encryptDeviceSecret($deviceSecret);

                $this->getEntityManager()->persist($deviceSecret);
            }
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Generate all missing device certificates for the provided device.
     */
    public function generateAllDeviceCertificates(): void
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $device = $this->getProvidedDeviceEntity();
        $deviceCertificates = $this->getDeviceCertificateEntities($device);

        foreach ($device->getDeviceType()->getCertificateTypes() as $deviceTypeCertificateType) {
            $found = false;
            foreach ($deviceCertificates as $deviceCertificate) {
                if ($deviceCertificate->getCertificateType()->getId() == $deviceTypeCertificateType->getCertificateType()->getId()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                if (PkiType::NONE !== $deviceTypeCertificateType->getCertificateType()->getPkiType()) {
                    $certificate = new Certificate();
                    $certificate->setDevice($device);
                    $certificate->setCertificateType($deviceTypeCertificateType->getCertificateType());
                    $this->getCertificateManager()->generateCertificate($certificate);
                }
            }
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Get a device type entity by its name.
     */
    public function getDeviceTypeByName(string $deviceTypeName): DeviceType
    {
        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => $deviceTypeName]);
        $this->assertNotNull($deviceType);

        return $deviceType;
    }

    /**
     * Get a device entity by type and identifier.
     */
    public function getDeviceEntity(DeviceType $deviceType, string $identifier): ?Device
    {
        $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');
        $queryBuilder->andWhere('d.identifier = :deviceIdentifier');
        $queryBuilder->setParameter('deviceIdentifier', $identifier);
        $queryBuilder->andWhere('d.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceType);

        $devices = $queryBuilder->getQuery()->getResult();

        $this->assertLessThanOrEqual(1, \count($devices), 'Multiple Device entities found.');

        return Arr::get($devices, '0');
    }

    /**
     * Validate if the provided reinstall flags configuration is valid for the device type.
     */
    public function isValidReinstallFlagsConfiguration(DeviceType $deviceType, array $reinstallFlagsConfiguration): bool
    {
        foreach (['1', '2', '3'] as $featureValue) {
            $hasFirmwareGetter = 'getHasFirmware'.$featureValue;
            if (!$deviceType->$hasFirmwareGetter() && Arr::get($reinstallFlagsConfiguration, 'reinstallFirmware'.$featureValue, false)) {
                return false;
            }
            $hasConfigGetter = 'getHasConfig'.$featureValue;
            if (!$deviceType->$hasConfigGetter() && Arr::get($reinstallFlagsConfiguration, 'reinstallConfig'.$featureValue, false)) {
                return false;
            }

            $hasAlwaysReinstallConfigGetter = 'getHasAlwaysReinstallConfig'.$featureValue;
            if ($deviceType->$hasAlwaysReinstallConfigGetter() && Arr::get($reinstallFlagsConfiguration, 'reinstallConfig'.$featureValue, false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update the device entity with the given reinstall flags configuration.
     */
    public function updateDeviceWithReinstallFlags(array $reinstallFlagsConfiguration): void
    {
        $device = $this->getProvidedDeviceEntity();

        foreach (['1', '2', '3'] as $featureValue) {
            $reinstallFirmwareSetter = 'setReinstallFirmware'.$featureValue;
            $reinstallConfigSetter = 'setReinstallConfig'.$featureValue;

            $reinstallFirmwareFlag = Arr::get($reinstallFlagsConfiguration, 'reinstallFirmware'.$featureValue, false);
            $reinstallConfigFlag = Arr::get($reinstallFlagsConfiguration, 'reinstallConfig'.$featureValue, false);

            $device->$reinstallFirmwareSetter($reinstallFirmwareFlag);
            $device->$reinstallConfigSetter($reinstallConfigFlag);
        }

        $this->getEntityManager()->persist($device);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->detach($device);
    }

    /**
     * Get a template entity by name for the provided device type.
     */
    public function getTemplate(string $templateName): Template
    {
        $deviceType = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceType');

        $queryBuilder = $this->getRepository(Template::class)->createQueryBuilder('t');
        $queryBuilder->andWhere('t.name = :name');
        $queryBuilder->setParameter('name', $templateName);
        $queryBuilder->andWhere('t.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceType);

        $template = $queryBuilder->getQuery()->getOneOrNullResult();

        $this->assertNotNull($template, 'Template not found for device type '.$deviceType->getName().' and name '.$templateName);

        return $template;
    }

    /**
     * Assign a template to the provided device entity.
     */
    public function assignTemplate(string $templateName): void
    {
        $template = $this->getTemplate($templateName);
        $device = $this->getProvidedDeviceEntity();

        $device->setTemplate($template);

        $this->getEntityManager()->persist($device);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->detach($device);
    }

    /**
     * Set the provided device entity to staging mode.
     */
    public function setDeviceToStaging(): void
    {
        $device = $this->getProvidedDeviceEntity();

        $device->setStaging(true);

        $this->getEntityManager()->persist($device);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->detach($device);
    }

    /**
     * Get all device secret entities for the given device.
     */
    public function getDeviceSecretEntities(Device $device): array
    {
        $queryBuilder = $this->getRepository(DeviceSecret::class)->createQueryBuilder('ds');
        $queryBuilder->andWhere('ds.device = :device');
        $queryBuilder->setParameter('device', $device);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Provide the device state to the API client, including secrets and certificates.
     */
    public function provideDeviceState(): void
    {
        $device = $this->getProvidedDeviceEntity();

        $deviceSecrets = $this->getDeviceSecretEntities($device);
        $deviceCertificates = $this->getDeviceCertificateEntities($device);

        $this->getApiClient()->provide(
            DeviceStateModel::class,
            [
                'deviceSecrets' => $this->getDeviceSecretsEncryptedValues($deviceSecrets),
                'deviceCertificates' => $this->getDeviceCertificatesSerials($deviceCertificates),
            ]
        );
    }

    /**
     * Get an array of encrypted values for the given device secrets.
     */
    public function getDeviceSecretsEncryptedValues(array $deviceSecrets): array
    {
        $secretsEncryptedValues = [];

        foreach ($deviceSecrets as $deviceSecret) {
            $secretsEncryptedValues[$deviceSecret->getDeviceTypeSecret()->getName()] = $deviceSecret->getSecretValue();
        }

        return $secretsEncryptedValues;
    }

    /**
     * Get an array of certificate serials for the given device certificates.
     */
    public function getDeviceCertificatesSerials(array $deviceCertificates): array
    {
        $certificatesSerials = [];
        foreach ($deviceCertificates as $deviceCertificate) {
            $certificateContent = $this->getCertificateTypeManager()->getCertificate($deviceCertificate);
            $this->assertNotNull($certificateContent, 'Certificate content not found for device certificate '.$deviceCertificate->getId());

            $certificateArray = openssl_x509_parse($certificateContent);

            $this->assertArrayHasKey('serialNumber', $certificateArray, 'Certificate serial number not found in certificate response');
            $this->assertNotNull($certificateArray['serialNumber'], 'Certificate serial number is null in certificate response');

            $certificatesSerials[$deviceCertificate->getCertificateType()->getName()] = $certificateArray['serialNumber'];
        }

        return $certificatesSerials;
    }

    /**
     * Get all device certificate entities for the given device.
     */
    public function getDeviceCertificateEntities(Device $device): array
    {
        $queryBuilder = $this->getRepository(Certificate::class)->createQueryBuilder('ds');
        $queryBuilder->andWhere('ds.device = :device');
        $queryBuilder->setParameter('device', $device);

        return $queryBuilder->getQuery()->getResult();
    }
}
