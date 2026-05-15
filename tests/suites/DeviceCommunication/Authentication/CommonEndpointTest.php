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

namespace Tests\Suites\DeviceCommunication\Authentication;

use App\Entity\Firmware;
use App\Enum\AuthenticationMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Builder\DeviceCommunicationTestMatrixBuilder;
use Tests\Utilities\DeviceCommunicationAbstract\AbstractDeviceCommunicationTestCase;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceSecretState;
use Tests\Utilities\DeviceCommunicationAbstract\Trait\DeviceAuthenticationProviderTrait;
use Tests\Utilities\DeviceCommunicationAbstract\Trait\DeviceAuthenticationTrait;
use Tests\Utilities\DeviceCommunicationAbstract\Trait\DeviceCommunicationFirmwareTrait;
use Tests\Utilities\DeviceCommunicationAbstract\Trait\DeviceCredentialsSetupTrait;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceModel;

/**
 * Tests device authentication behavior across production device types and authentication methods.
 *
 * This suite runs a set of common endpoint scenarios for each supported production device type
 * and authentication combination. Scenarios exercised include:
 *  - initial device communication (creation or rejection depending on authentication)
 *  - disabled-device handling
 *  - firmware authentication checks
 *
 * Workflow summary:
 * 1) Attempt initial request (may be adjusted to allow device creation when necessary)
 * 2) Ensure the device exists (create with NONE authentication if initial cannot create)
 * 3) Verify handling of DISABLED requests
 * 4) Verify firmware authentication checks
 */
class CommonEndpointTest extends AbstractDeviceCommunicationTestCase
{
    use DeviceAuthenticationProviderTrait;
    use DeviceAuthenticationTrait;
    use DeviceCommunicationFirmwareTrait;
    use DeviceCredentialsSetupTrait;

    public static function getFixtureGroups(): array
    {
        return \array_merge(
            parent::getFixtureGroups(),
            [TestFixtures\DeviceCommunication\DeviceAuthenticationFixtures::class],
        );
    }

    // Data provider: combines production device types with all reinstall-flag permutations
    public static function dataProvider(): array
    {
        return self::deviceAuthenticationProviderWithDeviceType(
            DeviceCommunicationTestMatrixBuilder::getProductionDeviceTypes()
        );
    }

    /**
     * Runs authentication scenario tests for a given device type and authentication configuration.
     *
     * Sequence for each test case:
     *  1. Initial request (may temporarily use NONE auth to allow device creation)
     *  2. Ensure device exists (create if necessary)
     *  3. DISABLED request verification
     *  4. Firmware authentication check
     */
    #[DataProvider('dataProvider')]
    public function testCommonEndpointAuthentication(string $deviceTypeName, AuthenticationMethod $deviceTypeAuthenticationMethod, AuthenticationMethod $requestAuthenticationMethod, array $credentials): void
    {
        /*
        Test logic sequence:
        1. Initial request:
            - If device-type authentication is X509, or BASIC/DIGEST with credentials from SECRET,
              an initial authenticated request cannot create the device because credentials/certificates must pre-exist.
              In those cases, temporarily set device type to NONE so the initial request can succeed and create the device.
            - For other combinations, the initial request may succeed or fail according to the test expectations.
            - If the initial request succeeds, the device exists and we proceed to Step 3.
            - If the initial request fails, the device does not exist and we create it in Step 2 so Steps 3 and 4 can run.
        2. Ensure device exists:
            - If the device was not created in Step 1, create it now using NONE authentication to allow successful creation.
        3. DISABLED request:
            - With the device created and configured for the test case, verify DISABLED request behavior.
        4. Firmware authentication check:
            - Create firmware and perform a firmware request to verify firmware-level authentication behavior.
        */

        // Step 1 — Attempt initial request (when applicable)
        if ($this->isInitialRequestPossibleToExecute($deviceTypeAuthenticationMethod, $requestAuthenticationMethod, $credentials)) {
            $this->provideDeviceTypeAuthenticationModel($deviceTypeName, '', $deviceTypeAuthenticationMethod, $requestAuthenticationMethod, $credentials);

            // Device secret cannot exist in this scenario
            $updatedCredentials = \array_merge([], $credentials);
            $updatedCredentials['deviceSecretState'] = DeviceSecretState::NOT_EXISTS;

            $this->getApiClient()->requestDeviceCommunication(
                requestType: $this->getExpectedRequestType(
                    successfulRequestType: DeviceCommunicationRequestTypeEnum::INITIAL,
                    deviceTypeAuthenticationMethod: $deviceTypeAuthenticationMethod,
                    requestAuthenticationMethod: $requestAuthenticationMethod,
                    credentials: $updatedCredentials
                ),
            );
        }

        // Step 2 — Ensure device exists (create with NONE auth if not created earlier)
        if (!$this->hasProvidedEntityParameter(DeviceModel::class, 'id')) {
            // Device does not exist — create it with NONE authentication to allow subsequent checks
            $this->provideDeviceTypeAuthenticationModel($deviceTypeName, '', AuthenticationMethod::NONE, AuthenticationMethod::NONE, []);

            // This request must succeed due to the temporary NONE configuration; device will be created
            $this->getApiClient()->requestDeviceCommunication(
                requestType: DeviceCommunicationRequestTypeEnum::INITIAL,
            );
        }

        // Step 3 — Verify DISABLED request behavior
        $this->provideDeviceTypeAuthenticationModel($deviceTypeName, '', $deviceTypeAuthenticationMethod, $requestAuthenticationMethod, $credentials);

        $this->getApiClient()->requestDeviceCommunication(
            requestType: $this->getExpectedRequestType(
                successfulRequestType: DeviceCommunicationRequestTypeEnum::DISABLED,
                deviceTypeAuthenticationMethod: $deviceTypeAuthenticationMethod,
                requestAuthenticationMethod: $requestAuthenticationMethod,
                credentials: $credentials
            ),
        );

        // Step 4 — Firmware authentication check
        // Device type and device are already configured from previous steps; create firmware and verify authentication checks
        $this->executeDeviceFirmwareAuthenticationTest($deviceTypeAuthenticationMethod, $requestAuthenticationMethod, $credentials);
    }
}
