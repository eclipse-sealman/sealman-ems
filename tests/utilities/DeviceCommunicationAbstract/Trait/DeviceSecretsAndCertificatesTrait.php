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

namespace Tests\Utilities\DeviceCommunicationAbstract\Trait;

use App\Enum\CommunicationProcedure;
use Tests\Utilities\DeviceCommunicationApiClient\DeviceCommunicationApiClientAssert;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;

/**
 * Trait is to be used in test cases that extend AbstractDeviceCommunicationTestCase.
 * Trait provides helper methods to provide device communication models for device communication tests
 * that focus on device secrets and certificates handling during device communication.
 *
 * Trait assumes 4 different device type setups:
 * - Device type with no secrets and no certificates
 * - Device type with secrets and no certificates
 * - Device type with no secrets and with certificates
 * - Device type with secrets and with certificates
 *
 * Trait provides methods to execute device communication requests and assert expected behavior
 */
trait DeviceSecretsAndCertificatesTrait
{
    protected function provideDeviceTypeModel(array $deviceTypeConfiguration, array $reinstallFlagsConfiguration): void
    {
        $deviceType = $this->getDeviceTypeByName($deviceTypeConfiguration['deviceTypeName']);

        if (!$this->isValidReinstallFlagsConfiguration($deviceType, $reinstallFlagsConfiguration)) {
            $this->markTestSkipped('Unsupported reinstall flags configuration for device type: '.$deviceType->getName());
        }

        $identifier = $this->getUniqueDeviceIdentifier($deviceType);

        $this->getApiClient()->provide(
            DeviceTypeModel::class,
            [
                'id' => $deviceType->getId(),
                'deviceType' => $deviceType,
                'deviceTypePrefix' => $deviceTypeConfiguration['deviceTypePrefix'],
                'deviceTypeSecrets' => $deviceTypeConfiguration['secrets'],
                'deviceTypeCertificates' => $deviceTypeConfiguration['certificates'],
                'deviceTypeDeviceName' => $deviceType->getDeviceName(),
                'deviceIdentifier' => $identifier,
                'deviceTypeRoutePrefix' => $deviceType->getRoutePrefix(),
                // In this cases, authentication method is NONE, so we do not need to provide username and password - set by getEnabledDeviceTypeWithNoAuthentication
                'deviceTypeCommunicationAuthenticationMethod' => $deviceType->getAuthenticationMethod(),
            ]
        );
    }

    protected function executeInitialDeviceCreationRequest(): void
    {
        // ? Requesting initial device communication to create a new device
        // Checking that responds with expected new device created response
        // Secrets and certificates are not created because device is disabled
        // Checking that device accumulated connections amount correctly
        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::INITIAL,
            variables: [
                'expectedDeviceConnectionAmount' => 0,
            ],
            asserts: [
                // Asserting that device entity exists in database (was created), and is disabled
                // Also asserting that device entity does not have any device secrets and certificates - because it is disabled, so none of them should be created
                // Also asserting propper amount of device connections
                DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_WITHOUT_DEVICE_SECRETS,
                DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_WITHOUT_DEVICE_CERTIFICATES,
                DeviceCommunicationApiClientAssert::DEVICE_CONNECTIONS_AMOUNT_COUNT,
            ],
        );
    }

    protected function executeDeviceRequestOnEnabledDevice(int $expectedDeviceConnectionAmount): void
    {
        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::SUCCESSFUL_RESPONSE,
            variables: [
                'expectedDeviceConnectionAmount' => $expectedDeviceConnectionAmount,
            ],
            asserts: [
                // Asserting that device entity exists in database (was created), and is enabled
                // Also asserting propper amount of device connections
                DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_ENABLED,
                DeviceCommunicationApiClientAssert::DEVICE_CONNECTIONS_AMOUNT_COUNT,
            ],
        );
    }

    protected function executeDeviceDisabledRequest(int $expectedDeviceConnectionAmount): void
    {
        $this->getApiClient()->requestDeviceCommunication(
            requestType: DeviceCommunicationRequestTypeEnum::SUCCESSFUL_RESPONSE,
            variables: [
                'expectedDeviceConnectionAmount' => $expectedDeviceConnectionAmount,
            ],
            asserts: [
                // Asserting that device entity exists in database (was created), and is disabled
                DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                // Also asserting propper amount of device connections
                DeviceCommunicationApiClientAssert::DEVICE_CONNECTIONS_AMOUNT_COUNT,
            ],
        );
    }

    /**
     * This method overrides $deviceSecretExpected value based on tests scenario
     * if secrets are not available for device type, then $deviceSecretExpected is always false
     * if secrets are available for device type, and device will always generate them, then $deviceSecretExpected is always true.
     */
    protected function isDeviceSecretsExpected(bool $deviceSecretExpected): bool
    {
        $deviceType = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceType');
        $deviceTypeSecrets = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceTypeSecrets');
        $communicationProcedure = $deviceType->getCommunicationProcedure();

        if (!$deviceTypeSecrets && $deviceSecretExpected) {
            return false;
        }

        if ($deviceTypeSecrets && !$deviceSecretExpected) {
            if (CommunicationProcedure::VPNCONTAINERCLIENT === $communicationProcedure) {
                return true;
            }

            return false;
        }

        return $deviceSecretExpected;
    }

    /**
     * This method calculates if device secrets or certificates could be generated even if config1 is not to be sent.
     */
    protected function isDeviceSecretsOrCertificatesGenerationOrRenewalExpected(?string $expectedProcessedReinstallFlag): bool
    {
        $deviceType = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceType');
        $communicationProcedure = $deviceType->getCommunicationProcedure();

        if ('reinstallConfig1' === $expectedProcessedReinstallFlag) {
            return true;
        }

        if (null === $expectedProcessedReinstallFlag) {
            return true;
        }

        if (CommunicationProcedure::VPNCONTAINERCLIENT === $communicationProcedure) {
            return true;
        }

        if (CommunicationProcedure::ROUTER === $communicationProcedure || CommunicationProcedure::ROUTER_DSA === $communicationProcedure) {
            if ('reinstallConfig2' === $expectedProcessedReinstallFlag) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method overrides $deviceCertificatesExpected value based on tests scenario
     * if certificates are not available for device type, then $deviceCertificatesExpected is always false
     * if certificates are available for device type, and device will always generate them, then $deviceCertificatesExpected is always true.
     * Vpn Container Client, is special case, because it always generates certificates, because it requires DEVICE_VPN to always be present and config will always be returned - so certificates will renew if outdated.
     */
    protected function isDeviceCertificatesExpected(bool $deviceCertificatesExpected): bool
    {
        $deviceType = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceType');
        $deviceTypeCertificates = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceTypeCertificates');
        $communicationProcedure = $deviceType->getCommunicationProcedure();

        if (CommunicationProcedure::VPNCONTAINERCLIENT === $communicationProcedure) {
            return true;
        }

        if (!$deviceTypeCertificates && $deviceCertificatesExpected) {
            // Edge Gateway with Vpn Container Client always generates certificates because it requires DEVICE_VPN to always be present and config will always be returned - so certificates will renew if outdated.
            if (CommunicationProcedure::EDGEGATEWAY_WITH_VPNCONTAINERCLIENT === $communicationProcedure) {
                return true;
            }

            return false;
        }

        return $deviceCertificatesExpected;
    }
}
