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

use App\Enum\AuthenticationMethod;
use App\Enum\CredentialsSource;
use Tests\Utilities\Builder\TestCaseMatrixBuilder;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceCertificateState;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceSecretState;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceUserState;

/**
 * Trait is to be used in test cases that extend AbstractDeviceCommunicationTestCase.
 * Trait provides helper methods to create test case providers for device communication tests
 * that focus on device authentication handling during device communication.
 *
 * Main method to use is deviceAuthenticationProviderWithDeviceType which generates test cases
 * for all authentication test cases for each given device type name.
 *
 * All authentication test cases consists of combinations of:
 * - Authentication method set in device type
 * - Authentication method used in device communication request - might be different than set in device type for negative testing
 * - Credentials setup expectations - described in DeviceCredentialsSetupTrait
 */
trait DeviceAuthenticationProviderTrait
{
    /**
     * Generate authentication test scenarios for a given device type array.
     */
    protected static function deviceAuthenticationProviderWithDeviceType(array $deviceTypeArray): array
    {
        return self::deviceAuthenticationProvider(
            TestCaseMatrixBuilder::withParameter(
                'deviceTypeName',
                $deviceTypeArray
            )
        );
    }

    /**
     * Methods provides array of test scenarios ([$deviceTypeAuthenticationMethod, $requestAuthenticationMethod, $credentials]) with all possible authentication settings.
     * Credentials array contains combinations for BASIC and DIGEST authentication methods.
     * DeviceType name and DeviceCommunication class are not SET due to tests requirements to be splitted, due to large amount of test cases.
     */
    protected static function deviceAuthenticationProvider(array $initialTestCasesArray = []): array
    {
        // Preparation of combinations for X509 authentication for X509
        $x509Authentication = TestCaseMatrixBuilder::withParameter(
            'certificateState',
            TestCaseMatrixBuilder::getEnumValues(DeviceCertificateState::class, 'certificateState_'),
        );

        $credentialAuthentication = self::getCredentialAuthenticationCases();

        // Since test cases use different credentials setup in device type - test cases are splited by credential type
        // NONE - no credentials
        $generatedAuthNoneCases = TestCaseMatrixBuilder::withParameter(
            'deviceTypeAuthenticationMethod',
            [
                'dtAuth_none' => AuthenticationMethod::NONE,
            ],
            $initialTestCasesArray
        );

        // All request authentication methods to be used in test cases
        // $requestAuthenticationMethodsValues = TestCaseMatrixBuilder::getEnumValues(AuthenticationMethod::class, 'reqAuth_');

        // Temporary hardcoding of request authentication methods - without MTLS and MTLS_SCEP, which are disabled for now - to be added in separate task
        $requestAuthenticationMethodsValues = [
            'reqAuth_none' => AuthenticationMethod::NONE,
            'reqAuth_basic' => AuthenticationMethod::BASIC,
            'reqAuth_digest' => AuthenticationMethod::DIGEST,
            'reqAuth_x509' => AuthenticationMethod::X509,
            // 'reqAuth_mTls' => AuthenticationMethod::MTLS, --- DISABLED FOR NOW - TO BE ADDED IN SEPARATE TASK ---
            // 'reqAuth_mTlsScep' => AuthenticationMethod::MTLS_SCEP, --- DISABLED FOR NOW - TO BE ADDED IN SEPARATE TASK ---
        ];

        $generatedAuthNoneCases = TestCaseMatrixBuilder::withParameter(
            'requestAuthenticationMethod',
            $requestAuthenticationMethodsValues,
            $generatedAuthNoneCases
        );

        $generatedAuthNoneCases = TestCaseMatrixBuilder::withParameter(
            'credentials',
            [
                // In case request will use other authentication method than NONE, valid credentials will be use in case of BASIC DIGEST and valid certificate in case of X509
                'noCredentials' => [],
            ],
            $generatedAuthNoneCases
        );

        // Credentials - BASIC and DIGEST
        $generatedAuthCredentialCases = TestCaseMatrixBuilder::withParameter(
            'deviceTypeAuthenticationMethod',
            [
                'dtAuth_basic' => AuthenticationMethod::BASIC,
                'dtAuth_digest' => AuthenticationMethod::DIGEST,
            ],
            $initialTestCasesArray
        );

        $generatedAuthCredentialCases = TestCaseMatrixBuilder::withParameter(
            'requestAuthenticationMethod',
            $requestAuthenticationMethodsValues,
            $generatedAuthCredentialCases
        );

        $generatedAuthCredentialCases = TestCaseMatrixBuilder::withParameter(
            'credentials',
             // In case request will use X509 - valid certificate will be used
            $credentialAuthentication,
            $generatedAuthCredentialCases
        );

        // X509 Credentials - X509
        $generatedX509CredentialCases = TestCaseMatrixBuilder::withParameter(
            'deviceTypeAuthenticationMethod',
            [
                'dtAuth_x509' => AuthenticationMethod::X509,
            ],
            $initialTestCasesArray
        );

        $generatedX509CredentialCases = TestCaseMatrixBuilder::withParameter(
            'requestAuthenticationMethod',
            $requestAuthenticationMethodsValues,
            $generatedX509CredentialCases
        );

        $generatedX509CredentialCases = TestCaseMatrixBuilder::withParameter(
            'credentials',
             // In case request will use other authentication method , valid credentials will be use in case of BASIC DIGEST
            $x509Authentication,
            $generatedX509CredentialCases
        );

        $generatedCases = array_merge($generatedAuthNoneCases, $generatedAuthCredentialCases, $generatedX509CredentialCases);

        return $generatedCases;
    }

    protected static function getCredentialAuthenticationCases(): array
    {
        // Preparation of combinations for credentials authentication for BASIC and DIGEST
        // During testing two groups are invalid:
        // 1. When CredentialsSource::USER is used and useDeviceSecret is true - as device secret is not used in this case
        // 2. When CredentialsSource::SECRET is used and useDeviceSecret is false - as device secret is used in this case
        // Other combinations are valid and will be tested

        // Commenting out unused option for better readability
        $credentialUseDeviceSecretAuthentication = TestCaseMatrixBuilder::withParameter(
            'credentialSource',
            [
                // 'credentialSource_user' => CredentialsSource::USER,
                'credentialSource_secret' => CredentialsSource::SECRET,
                'credentialSource_both' => CredentialsSource::BOTH,
                'credentialSource_userIfSecretMissing' => CredentialsSource::USER_IF_SECRET_MISSING,
            ]
        );

        $credentialUseDeviceSecretAuthentication = TestCaseMatrixBuilder::withParameter(
            'deviceUserState',
            TestCaseMatrixBuilder::getEnumValues(DeviceUserState::class, 'deviceUserState_'),
            $credentialUseDeviceSecretAuthentication
        );
        $credentialUseDeviceSecretAuthentication = TestCaseMatrixBuilder::withParameter(
            'deviceSecretState',
            TestCaseMatrixBuilder::getEnumValues(DeviceSecretState::class, 'deviceSecretState_'),
            $credentialUseDeviceSecretAuthentication
        );

        $credentialUseDeviceSecretAuthentication = TestCaseMatrixBuilder::withParameter(
            'useDeviceSecret',
            [
                'useDeviceSecret' => true,
                // 'useDeviceUser' => false,
            ],
            $credentialUseDeviceSecretAuthentication
        );

        $credentialUseUserAuthentication = TestCaseMatrixBuilder::withParameter(
            'credentialSource',
            [
                'credentialSource_user' => CredentialsSource::USER,
                // 'credentialSource_secret' => CredentialsSource::SECRET,
                'credentialSource_both' => CredentialsSource::BOTH,
                'credentialSource_userIfSecretMissing' => CredentialsSource::USER_IF_SECRET_MISSING,
            ]
        );

        $credentialUseUserAuthentication = TestCaseMatrixBuilder::withParameter(
            'deviceUserState',
            TestCaseMatrixBuilder::getEnumValues(DeviceUserState::class, 'deviceUserState_'),
            $credentialUseUserAuthentication
        );
        $credentialUseUserAuthentication = TestCaseMatrixBuilder::withParameter(
            'deviceSecretState',
            TestCaseMatrixBuilder::getEnumValues(DeviceSecretState::class, 'deviceSecretState_'),
            $credentialUseUserAuthentication
        );

        $credentialUseUserAuthentication = TestCaseMatrixBuilder::withParameter(
            'useDeviceSecret',
            [
                // 'useDeviceSecret' => true,
                'useDeviceUser' => false,
            ],
            $credentialUseUserAuthentication
        );

        $credentialAuthentication = array_merge($credentialUseDeviceSecretAuthentication, $credentialUseUserAuthentication);

        return $credentialAuthentication;
    }
}
