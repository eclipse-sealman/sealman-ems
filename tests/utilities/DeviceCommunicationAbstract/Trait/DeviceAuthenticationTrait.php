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

use App\Entity\Firmware;
use App\Enum\AuthenticationMethod;
use App\Enum\CredentialsSource;
use Symfony\Component\HttpFoundation\Response;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceCertificateState;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceSecretState;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceUserState;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceCertificateModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceCredentialModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;

/**
 * Trait is to be used in test cases that extend AbstractDeviceCommunicationTestCase.
 * Trait provides helper methods to setup device type authentication and assert responses for device communication tests.
 *
 * Main methods to use are:
 * - provideDeviceTypeAuthenticationModel - to setup device type with given authentication method and provide device communication model accordingly
 * - provideInitialDeviceTypeAuthenticationModel - to setup device type with given authentication method and request authentication method, but authentication method might be changed to NONE to make sure first request is successful and device is created
 *
 * Trait provides methods to determine expected request response status and expected request type based on device type authentication method,
 * request authentication method and credentials used for authentication.
 */
trait DeviceAuthenticationTrait
{
    use DeviceCredentialsSetupTrait;

    // Sets up DeviceType with given authentication method and request authentication method, but authentication method might be changed to NONE to make sure first request is successful and device is created
    // Method is not allways changing $deviceTypeAuthenticationMethod to NONE - because in case of using BASIC or DIGEST with valid user credentials (not CredentialSource::SECRET) - initial request is expected to be successful - this is regular production scenario
    protected function provideInitialDeviceTypeAuthenticationModel(string $deviceTypeName, string $deviceTypePrefix, AuthenticationMethod $deviceTypeAuthenticationMethod, AuthenticationMethod $requestAuthenticationMethod, array $credentials): void
    {
        // Making copy of credentials to avoid changing original array
        $updatedCredentials = \array_merge([], $credentials);

        // This is to
        if (AuthenticationMethod::X509 === $deviceTypeAuthenticationMethod) {
            // X509 requires device to have certificate and for that device needs to exists. This can't be done on initial request
            // So changing device type authentication method to NONE to allow initial request to be successful and device to be created
            $updatedCredentials['credentialSource'] = CredentialsSource::USER;
            $deviceTypeAuthenticationMethod = AuthenticationMethod::NONE;
            $requestAuthenticationMethod = AuthenticationMethod::NONE;
        }

        if (\in_array($deviceTypeAuthenticationMethod, [AuthenticationMethod::BASIC, AuthenticationMethod::DIGEST], true)) {
            // To use secrets as credentials, device needs to exists. This can't be done on initial request
            // So changing to authenticate using user credentials to allow initial request to be successful and device to be created
            if (CredentialsSource::SECRET === $credentials['credentialSource']) {
                $updatedCredentials['credentialSource'] = CredentialsSource::USER;
            }

            if (true === $credentials['useDeviceSecret']) {
                $updatedCredentials['useDeviceSecret'] = false;
            }

            $updatedCredentials['deviceUserState'] = DeviceUserState::VALID;
        }

        $this->provideDeviceTypeAuthenticationModel($deviceTypeName, $deviceTypePrefix, $deviceTypeAuthenticationMethod, $requestAuthenticationMethod, $updatedCredentials);
    }

    protected function provideDeviceTypeAuthenticationModel(string $deviceTypeName, string $deviceTypePrefix, AuthenticationMethod $deviceTypeAuthenticationMethod, AuthenticationMethod $requestAuthenticationMethod, array $credentials): void
    {
        $deviceType = $this->getDeviceTypeByName($deviceTypeName);
        $deviceType->setEnabled(true);
        $deviceType->setAuthenticationMethod($deviceTypeAuthenticationMethod);

        if (isset($credentials['credentialSource'])) {
            $deviceType->setCredentialsSource($credentials['credentialSource']);
        }

        $this->setupDeviceUserState($deviceType, $credentials);
        $this->setupDeviceTypeSecretState($deviceType, $credentials);
        $this->setupDeviceSecretState($deviceType, $credentials);
        $this->setupDeviceTypeCertificateState($deviceType, $credentials);
        $this->setupDeviceCertificateState($deviceType, $credentials);

        $this->provideCredentials($credentials);

        $this->getEntityManager()->persist($deviceType);
        $this->getEntityManager()->flush();

        if ($this->hasProvidedEntityParameter(DeviceTypeModel::class, 'deviceIdentifier')) {
            $deviceIdentifier = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceIdentifier');
        } else {
            $deviceIdentifier = $this->getUniqueDeviceIdentifier($deviceType);
        }

        $username = $this->getProvidedEntityParameter(DeviceCredentialModel::class, 'username');
        $password = $this->getProvidedEntityParameter(DeviceCredentialModel::class, 'password');
        $sslCertificate = $this->getProvidedEntityParameter(DeviceCertificateModel::class, 'sslCertificate');
        $sslKey = $this->getProvidedEntityParameter(DeviceCertificateModel::class, 'sslKey');

        $this->getApiClient()->provide(
            DeviceTypeModel::class,
            [
                'id' => $deviceType->getId(),
                'deviceType' => $deviceType,
                'deviceTypePrefix' => $deviceTypePrefix,
                'deviceTypeSecrets' => 'none', // ?
                'deviceTypeCertificates' => 'none', // ?
                'deviceTypeDeviceName' => $deviceType->getDeviceName(),
                'deviceIdentifier' => $deviceIdentifier,
                'deviceTypeRoutePrefix' => $deviceType->getRoutePrefix(),
                // In this cases, authentication method is NONE, so we do not need to provide username and password - set by getEnabledDeviceTypeWithNoAuthentication
                'deviceTypeCommunicationAuthenticationMethod' => $requestAuthenticationMethod,
                'username' => $username,
                'password' => $password,
                'sslCertificate' => $sslCertificate,
                'sslKey' => $sslKey,
            ]
        );
    }

    /**
     * Checks if initial request is possible to execute with given device type authentication method, request authentication method and credentials.
     * Initial request is not possible if device type authentication method is X509 - because device needs to exist before adding certificate
     * Initial request is not possible if device type authentication method is BASIC or DIGEST and credentials source is SECRET - because device needs to exist before adding secret.
     */
    protected function isInitialRequestPossibleToExecute(AuthenticationMethod $deviceTypeAuthenticationMethod, AuthenticationMethod $requestAuthenticationMethod, array $credentials): bool
    {
        // Device needs to exist before adding x509 certificate
        if (AuthenticationMethod::X509 === $deviceTypeAuthenticationMethod) {
            return false;
        }

        if (\in_array($deviceTypeAuthenticationMethod, [AuthenticationMethod::BASIC, AuthenticationMethod::DIGEST], true)) {
            // To use secrets as credentials, device needs to exists
            if (CredentialsSource::SECRET === $credentials['credentialSource']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if firmware is secured for the provided device type.
     */
    public function isFirmwareSecured(): bool
    {
        $deviceType = $this->getProvidedDeviceTypeEntity();

        return $this->getDeviceCommunicationFactory()->getDeviceCommunicationByDeviceType($deviceType)->isFirmwareSecured();
    }

    protected function getExpectedRequestType(DeviceCommunicationRequestTypeEnum $successfulRequestType, AuthenticationMethod $deviceTypeAuthenticationMethod, AuthenticationMethod $requestAuthenticationMethod, array $credentials): DeviceCommunicationRequestTypeEnum
    {
        $expectedResponseStatus = $this->getExpectedRequestResponseStatus($deviceTypeAuthenticationMethod, $requestAuthenticationMethod, $credentials);

        if (Response::HTTP_OK === $expectedResponseStatus) {
            return $successfulRequestType;
        }

        if (Response::HTTP_UNAUTHORIZED === $expectedResponseStatus) {
            return DeviceCommunicationRequestTypeEnum::UNAUTHORIZED_RESPONSE;
        }

        if (Response::HTTP_FORBIDDEN === $expectedResponseStatus) {
            if (AuthenticationMethod::X509 === $deviceTypeAuthenticationMethod) {
                // In this case certificate is invalid or missing so it is unauthorized
                return DeviceCommunicationRequestTypeEnum::UNAUTHORIZED_RESPONSE;
            }

            return DeviceCommunicationRequestTypeEnum::FORBIDDEN_RESPONSE;
        }

        throw new \Exception('Unable to determine expected request type for device type authentication method "'.$deviceTypeAuthenticationMethod->value.'", request authentication method "'.$requestAuthenticationMethod->value.'" and credentials "'.json_encode($credentials).'"');
    }

    /**
     * Determine expected response status for firmware download security request.
     *
     * @return int HTTP status code
     */
    protected function getExpectedRequestResponseStatus(AuthenticationMethod $deviceTypeAuthenticationMethod, AuthenticationMethod $requestAuthenticationMethod, array $credentials): int
    {
        // If device type authentication method is NONE - request is always successful
        if (AuthenticationMethod::NONE === $deviceTypeAuthenticationMethod) {
            return Response::HTTP_OK; // 200
        }

        // If device type authentication method is not NONE - request authentication method must match device type authentication method
        if ($deviceTypeAuthenticationMethod !== $requestAuthenticationMethod) {
            if (\in_array($deviceTypeAuthenticationMethod, [AuthenticationMethod::BASIC, AuthenticationMethod::DIGEST], true)) {
                // In this case user exists so it is logged in but unauthorized
                if (Response::HTTP_OK === $this->getDeviceUserCredentialsExpectedRequestResponseStatus($credentials, false)) {
                    return Response::HTTP_UNAUTHORIZED; // 401
                }

                return $this->getDeviceUserCredentialsExpectedRequestResponseStatus($credentials, false); // 403 or 401
            }

            return Response::HTTP_FORBIDDEN; // 403
        }

        // If device type authentication method is X509 - certificate must be valid or valid self-signed
        if (AuthenticationMethod::X509 === $deviceTypeAuthenticationMethod) {
            $validArray = [DeviceCertificateState::VALID, DeviceCertificateState::VALID_SELF_SIGNED];
            if (!in_array($credentials['certificateState'], $validArray, true)) {
                return Response::HTTP_UNAUTHORIZED; // 401
            } else {
                return Response::HTTP_OK; // 200
            }
        }

        // Handling credential based authentications - BASIC and DIGEST
        if (\in_array($deviceTypeAuthenticationMethod, [AuthenticationMethod::BASIC, AuthenticationMethod::DIGEST], true)) {
            // Using ifs instead of switch for better readability

            // Only device secret can be used for authentication
            if (CredentialsSource::SECRET === $credentials['credentialSource']) {
                return $this->getDeviceSecretCredentialsExpectedRequestResponseStatus($credentials);
            }

            // Only device user can be used for authentication
            if (CredentialsSource::USER === $credentials['credentialSource']) {
                return $this->getDeviceUserCredentialsExpectedRequestResponseStatus($credentials, true);
            }

            // Both device user and device secret can be used for authentication
            if (CredentialsSource::BOTH === $credentials['credentialSource']) {
                if ($credentials['useDeviceSecret']) {
                    // If device secret is used for authentication - device secret must be valid
                    return $this->getDeviceSecretCredentialsExpectedRequestResponseStatus($credentials);
                } else {
                    // Device user is used for authentication - device user must be valid
                    return $this->getDeviceUserCredentialsExpectedRequestResponseStatus($credentials, true);
                }
            }

            // If device secret exists it has to be used for authentication if not user is used
            if (CredentialsSource::USER_IF_SECRET_MISSING === $credentials['credentialSource']) {
                $nonExistentArray = [DeviceSecretState::NOT_IN_DEVICE_TYPE_CREDENTIAL, DeviceSecretState::DIFFERENT_DEVICE_TYPE, DeviceSecretState::NOT_EXISTS];
                if (in_array($credentials['deviceSecretState'], $nonExistentArray, true)) {
                    // Device secret does not exist - user must be used for authentication
                    return $this->getDeviceUserCredentialsExpectedRequestResponseStatus($credentials, true);
                } else {
                    // Device secret exists - it must be used for authentication
                    return $this->getDeviceSecretCredentialsExpectedRequestResponseStatus($credentials);
                }
            }
        }

        throw new \Exception('Unable to determine expected response status for device type authentication method "'.$deviceTypeAuthenticationMethod->value.'", request authentication method "'.$requestAuthenticationMethod->value.'" and credentials "'.json_encode($credentials).'"');
    }

    /**
     * Determine expected response status for request with given device type authentication method, request authentication method, and credentials.
     *
     * @return int HTTP status code
     */
    protected function getExpectedRequestResponseStatusForFirmwareDownloadSecurity(AuthenticationMethod $deviceTypeAuthenticationMethod, AuthenticationMethod $requestAuthenticationMethod, array $credentials): int
    {
        if (!$this->isFirmwareSecured()) {
            return Response::HTTP_NO_CONTENT; // 204
        }

        $expectedResponseStatus = $this->getExpectedRequestResponseStatus($deviceTypeAuthenticationMethod, $requestAuthenticationMethod, $credentials);
        if (Response::HTTP_OK === $expectedResponseStatus) {
            $expectedResponseStatus = Response::HTTP_NO_CONTENT; // 204 instead of 200 this is how DeviceFirmwareSecurityController returns success
        }
        if (Response::HTTP_FORBIDDEN === $expectedResponseStatus) {
            $expectedResponseStatus = Response::HTTP_UNAUTHORIZED; // 401 instead of 403 this is how DeviceFirmwareSecurityController returns success
        }

        return $expectedResponseStatus;
    }

    /**
     * Helper: Determine expected response status for request with device user credentials.
     *
     * Assumes only device user is used for authentication.
     *
     * @return int HTTP status code
     */
    protected function getDeviceUserCredentialsExpectedRequestResponseStatus(array $credentials, bool $validAuthenticationMethod): int
    {
        if (true === $credentials['useDeviceSecret']) {
            return Response::HTTP_UNAUTHORIZED; // 401
        }

        switch ($credentials['deviceUserState']) {
            case DeviceUserState::NOT_EXISTS:
            case DeviceUserState::DISABLED:
            case DeviceUserState::NO_ROLE_DEVICE:
            case DeviceUserState::INVALID_PASSWORD:
                return Response::HTTP_UNAUTHORIZED; // 401

            case DeviceUserState::NO_DEVICE_TYPE_ROLE:
                if (true === $validAuthenticationMethod) {
                    return Response::HTTP_FORBIDDEN; // 403
                }

                return Response::HTTP_UNAUTHORIZED; // 401

            case DeviceUserState::VALID:
                return Response::HTTP_OK; // 200
        }
    }

    /**
     * Helper: Determine expected response status for request with device secret credentials.
     *
     * Assumes only device secret is used for authentication.
     *
     * @return int HTTP status code
     */
    protected function getDeviceSecretCredentialsExpectedRequestResponseStatus(array $credentials): int
    {
        if (false === $credentials['useDeviceSecret']) {
            return Response::HTTP_UNAUTHORIZED; // 401
        }

        $validArray = [DeviceSecretState::VALID, DeviceSecretState::EXPIRED, DeviceSecretState::WILL_BE_RENEWED, DeviceSecretState::NAME_SAME_AS_USERNAME];
        if (!in_array($credentials['deviceSecretState'], $validArray, true)) {
            return Response::HTTP_UNAUTHORIZED; // 401
        } else {
            return Response::HTTP_OK; // 200
        }
    }
}
