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

use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\PkiType;
use App\Enum\SecretValueBehaviour;
use Carve\ApiBundle\Helper\Arr;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceStateModel;

/**
 * Code separated for readability.
 * To be used only with AbstractDeviceCommunicationTestCase class.
 */
trait AssertionsTrait
{
    // Test case helpers for code reusability
    /**
     * Assert that the device has the expected number of connections.
     */
    public function assertDeviceConnectionsAmountCount(Device $device, int $expectedAmount, ?string $messageSuffix = null): void
    {
        $this->assertEquals($expectedAmount, $device->getConnectionAmount(), 'Invalid amount of connections for device '.$device->getIdentifier().($messageSuffix ? ' '.$messageSuffix : ''));
    }

    /**
     * Assert that a device entity does not exist for the given type and identifier.
     */
    public function assertDeviceEntityNotExists(DeviceType $deviceType, string $identifier, ?string $messageSuffix = null): void
    {
        $this->assertNull($this->getDeviceEntity($deviceType, $identifier), "Device entity found for device type {$deviceType->getName()} and identifier {$identifier}.".($messageSuffix ? ' '.$messageSuffix : ''));
    }

    /**
     * Assert that a device entity exists for the given type and identifier.
     */
    public function assertDeviceEntityExists(DeviceType $deviceType, string $identifier, ?string $messageSuffix = null): Device
    {
        $device = $this->getDeviceEntity($deviceType, $identifier);
        $this->assertNotNull($device, "Device entity not found for device type {$deviceType->getName()} and identifier {$identifier}.".($messageSuffix ? ' '.$messageSuffix : ''));

        return $device;
    }

    /**
     * Assert that a device entity exists and is disabled.
     */
    public function assertDeviceEntityExistsAndDisabled(DeviceType $deviceType, string $identifier, ?string $messageSuffix = null): Device
    {
        $device = $this->assertDeviceEntityExists($deviceType, $identifier, $messageSuffix);
        $this->assertFalse($device->getEnabled(), "Device entity is enabled for device type {$deviceType->getName()} and identifier {$identifier}.".($messageSuffix ? ' '.$messageSuffix : ''));

        return $device;
    }

    /**
     * Assert that a device entity exists and is enabled.
     */
    public function assertDeviceEntityExistsAndEnabled(DeviceType $deviceType, string $identifier, ?string $messageSuffix = null): Device
    {
        $device = $this->assertDeviceEntityExists($deviceType, $identifier, $messageSuffix);
        $this->assertTrue($device->getEnabled(), "Device entity is disabled for device type {$deviceType->getName()} and identifier {$identifier}.".($messageSuffix ? ' '.$messageSuffix : ''));

        return $device;
    }

    /**
     * Assert that the device's reinstall flags match the expected configuration.
     */
    public function assertDeviceReinstallFlags(array $expectedReinstallFlagsConfiguration): void
    {
        $device = $this->getProvidedDeviceEntity();

        foreach (['1', '2', '3'] as $featureValue) {
            $reinstallFirmwareGetter = 'getReinstallFirmware'.$featureValue;
            $reinstallConfigGetter = 'getReinstallConfig'.$featureValue;

            $reinstallFirmwareFlag = Arr::get($expectedReinstallFlagsConfiguration, 'reinstallFirmware'.$featureValue, false);
            $reinstallConfigFlag = Arr::get($expectedReinstallFlagsConfiguration, 'reinstallConfig'.$featureValue, false);

            $this->assertEquals($reinstallFirmwareFlag, $device->$reinstallFirmwareGetter(), 'Invalid reinstall firmware flag for feature '.$featureValue);
            $this->assertEquals($reinstallConfigFlag, $device->$reinstallConfigGetter(), 'Invalid reinstall config flag for feature '.$featureValue);
        }
    }

    /**
     * Assert that the device has no secrets.
     */
    public function assertDeviceWithoutDeviceSecrets(Device $device, ?string $messageSuffix = null): array
    {
        $deviceSecrets = $this->getDeviceSecretEntities($device);
        $this->assertCount(0, $deviceSecrets, 'Device secrets found for device '.$device->getIdentifier().($messageSuffix ? ' '.$messageSuffix : ''));

        return $deviceSecrets;
    }

    /**
     * Assert that the device has no certificates.
     */
    public function assertDeviceWithoutDeviceCertificates(Device $device): array
    {
        $deviceCertificates = $this->getDeviceCertificateEntities($device);
        $this->assertCount(0, $deviceCertificates, 'Device certificates found for device '.$device->getIdentifier());

        return $deviceCertificates;
    }

    /**
     * Assert the state of device certificates, checking for renewal if expected.
     */
    public function assertDeviceCertificatesState(bool $expectRenewal = false): void
    {
        $device = $this->getProvidedDeviceEntity();
        $providedDeviceCertificatesSerials = $this->getRequiredProvidedEntityParameter(DeviceStateModel::class, 'deviceCertificates');

        $deviceCertificates = $this->getDeviceCertificateEntities($device);
        $certificateSerials = $this->getDeviceCertificatesSerials($deviceCertificates);

        if ($expectRenewal) {
            $this->assertDeviceCertificatesSerialsChanged($providedDeviceCertificatesSerials, $certificateSerials);
        } else {
            $this->assertDeviceCertificatesSerialsNotChanged($providedDeviceCertificatesSerials, $certificateSerials);
        }
    }

    /**
     * Assert the existence of device secrets, optionally checking for auto-generated or all secrets.
     */
    public function assertDeviceSecretsExists(
        bool $expectAutoGenerated = false,
        bool $expectAll = false,
        ?string $messageSuffix = null
    ): void {
        $device = $this->getProvidedDeviceEntity();
        $deviceSecrets = $this->getDeviceSecretEntities($device);

        if (!$expectAutoGenerated && !$expectAll) {
            $this->assertCount(0, $deviceSecrets, 'Device secrets found for device '.$device->getIdentifier().($messageSuffix ? ' '.$messageSuffix : ''));

            return;
        }

        // In this testsuite we assume that all generate-able device secrets will be generated and all renewable device secrets will be renewed during communication
        foreach ($device->getDeviceType()->getDeviceTypeSecrets() as $deviceTypeSecret) {
            $expectExists = $expectAll;
            if (!$expectAll && $expectAutoGenerated && $deviceTypeSecret->getUseAsVariable() && \in_array($deviceTypeSecret->getSecretValueBehaviour(), SecretValueBehaviour::getGenerateEnums())) {
                $expectExists = true;
            }

            $found = false;
            foreach ($deviceSecrets as $deviceSecret) {
                if ($deviceSecret->getDeviceTypeSecret()->getId() == $deviceTypeSecret->getId()) {
                    $found = true;
                    break;
                }
            }

            if ($expectExists) {
                $this->assertTrue($found, 'Device secret not found for device '.$device->getIdentifier().' and deviceTypeSecret '.$deviceTypeSecret->getName().($messageSuffix ? ' '.$messageSuffix : ''));
            } else {
                $this->assertFalse($found, 'Device secret found for device '.$device->getIdentifier().' and deviceTypeSecret '.$deviceTypeSecret->getName().($messageSuffix ? ' '.$messageSuffix : ''));
            }
        }
    }

    /**
     * Assert the state of device secrets, checking for renewal if expected.
     */
    public function assertDeviceSecretsState(bool $expectRenewal = false, ?string $messageSuffix = null): void
    {
        $device = $this->getProvidedDeviceEntity();
        $providedDeviceSecretsValues = $this->getRequiredProvidedEntityParameter(DeviceStateModel::class, 'deviceSecrets');

        $deviceSecrets = $this->getDeviceSecretEntities($device);

        if (!$expectRenewal) {
            $secretValues = $this->getDeviceSecretsEncryptedValues($deviceSecrets);
            $this->assertDeviceSecretsValuesNotChanged($providedDeviceSecretsValues, $secretValues);

            return;
        }

        // In this testsuite we assume that all generate-able device secrets will be generated and all renewable device secrets will be renewed during communication
        foreach ($device->getDeviceType()->getDeviceTypeSecrets() as $deviceTypeSecret) {
            $expectRenewed = false;
            if ($deviceTypeSecret->getUseAsVariable() && \in_array($deviceTypeSecret->getSecretValueBehaviour(), SecretValueBehaviour::getRenewEnums())) {
                $expectRenewed = true;
            }

            $deviceSecretValue = null;
            foreach ($deviceSecrets as $deviceSecret) {
                if ($deviceSecret->getDeviceTypeSecret()->getId() == $deviceTypeSecret->getId()) {
                    $deviceSecretValue = $deviceSecret->getSecretValue();
                    break;
                }
            }

            if (null === $deviceSecretValue) {
                continue; // Device secret not found, skip
            }

            $key = $deviceTypeSecret->getName();
            if ($expectRenewed) {
                $this->assertNotEquals($deviceSecretValue, $providedDeviceSecretsValues[$key], 'Secret value not changed for secret '.$key.($messageSuffix ? ' '.$messageSuffix : ''));
            } else {
                $this->assertEquals($deviceSecretValue, $providedDeviceSecretsValues[$key], 'Secret value changed for secret '.$key.($messageSuffix ? ' '.$messageSuffix : ''));
            }
        }
    }

    /**
     * Assert that device secret values have not changed.
     */
    public function assertDeviceSecretsValuesNotChanged(array $initialSecretsEncryptedValues, array $secretsEncryptedValues, ?string $messageSuffix = null): void
    {
        // List keys that are not in both arrays
        $keysTest = array_merge(array_diff_key($initialSecretsEncryptedValues, $secretsEncryptedValues), array_diff_key($secretsEncryptedValues, $initialSecretsEncryptedValues));
        $this->assertCount(0, $keysTest, 'Secrets list has changed: '.implode(', ', array_keys($keysTest)).($messageSuffix ? ' '.$messageSuffix : ''));

        // Since keys are the same we can simplitfy this loop
        foreach ($initialSecretsEncryptedValues as $key => $initialValue) {
            $this->assertEquals($initialValue, $secretsEncryptedValues[$key], 'Secret value changed for secret '.$key.($messageSuffix ? ' '.$messageSuffix : ''));
        }
    }

    /**
     * Assert that device certificate serials have not changed.
     */
    public function assertDeviceCertificatesSerialsNotChanged(array $initialCertificateSerials, array $certificatesSerials): void
    {
        // List keys that are not in both arrays
        $keysTest = array_merge(array_diff_key($initialCertificateSerials, $certificatesSerials), array_diff_key($certificatesSerials, $initialCertificateSerials));
        $this->assertCount(0, $keysTest, 'Certificate types list has changed: '.implode(', ', array_keys($keysTest)));

        // Since keys are the same we can simplitfy this loop
        foreach ($initialCertificateSerials as $key => $initialSerial) {
            $this->assertEquals($initialSerial, $certificatesSerials[$key], 'Certificate serial number changed for certificate type '.$key);
        }
    }

    /**
     * Assert that device certificate serials have changed as expected.
     */
    public function assertDeviceCertificatesSerialsChanged(array $initialCertificateSerials, array $certificatesSerials): void
    {
        // List keys that are not in both arrays
        $keysTest = array_merge(array_diff_key($initialCertificateSerials, $certificatesSerials), array_diff_key($certificatesSerials, $initialCertificateSerials));
        $this->assertCount(0, $keysTest, 'Certificate types list has changed: '.implode(', ', array_keys($keysTest)));

        $device = $this->getProvidedDeviceEntity();
        $deviceType = $device->getDeviceType();

        foreach ($deviceType->getCertificateTypes() as $deviceTypeCertificateType) {
            if (!Arr::has($initialCertificateSerials, $deviceTypeCertificateType->getCertificateType()->getName())) {
                continue; // Certificate type not found in initial serials, skip
            }

            $expectedChange = false;
            if ($deviceTypeCertificateType->getIsCertificateTypeAvailable() && PkiType::NONE != $deviceTypeCertificateType->getCertificateType()->getPkiType() && $deviceTypeCertificateType->getEnableCertificatesAutoRenew()) {
                $expectedChange = true; // Certificate type is available, so serial number should not change
            }

            if (!$expectedChange) {
                $this->assertEquals($initialCertificateSerials[$deviceTypeCertificateType->getCertificateType()->getName()], $certificatesSerials[$deviceTypeCertificateType->getCertificateType()->getName()], 'Certificate serial number changed for certificate type '.$deviceTypeCertificateType->getCertificateType()->getName());
            } else {
                $this->assertNotEquals($initialCertificateSerials[$deviceTypeCertificateType->getCertificateType()->getName()], $certificatesSerials[$deviceTypeCertificateType->getCertificateType()->getName()], 'Certificate serial number not changed for certificate type '.$deviceTypeCertificateType->getCertificateType()->getName());
            }
        }
    }
}
