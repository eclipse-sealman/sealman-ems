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

use App\Entity\Certificate;
use App\Entity\CertificateType;
use App\Entity\Device;
use App\Entity\DeviceSecret;
use App\Entity\DeviceType;
use App\Entity\DeviceTypeCertificateType;
use App\Entity\DeviceTypeSecret;
use App\Entity\User;
use App\Entity\UserDeviceType;
use App\Enum\SecretValueBehaviour;
use App\Enum\UserRole;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceCertificateState;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceSecretState;
use Tests\Utilities\DeviceCommunicationAbstract\Enum\DeviceUserState;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceCertificateModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceCredentialModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceCredentialUseDeviceSecretModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceCredentialUseUserModel;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceModel;

/**
 * Trait is to be used in test cases that extend AbstractDeviceCommunicationTestCase.
 * Trait provides helper methods to provide device communication credentials for device communication endpoint requests.
 * Requests are to be executed by DeviceCommunicationApiClient in AbstractDeviceCommunicationTestCase base class.
 *
 * Process of providing credentials includes:
 * - Setting up device entity state
 * - Setting up device type state
 * - Setting up device type secret state
 * - Setting up device secret state
 * - Setting up device authentication user state
 * - Setting up device type certificate state
 * - Setting up device certificate state
 *
 * Expected states are defined in $credentials parameter array:
 * - 'noCredentials' - empty array, no credentials will be provided e.g. for NONE authentication method
 * - 'deviceUserState' - state of device authentication user - value of DeviceUserState enum
 * - 'deviceSecretState' - state of device secret - value of DeviceSecretState enum
 * - 'certificateState' - state of device certificate - value of DeviceCertificateState enum
 * - 'credentialSource' - defines whether device secret or device user credentials are to be used value of CredentialSource enum
 * - 'useDeviceSecret' - boolean value defining whether device secret credentials are to be used (true) or device user credentials (false), for request authentication
 *
 * Credentials array might be prepared manually (for specific test cases) or
 * test case matrix data provider generator methods from DeviceAuthenticationTrait can be used (for broad coverage).
 */
trait DeviceCredentialsSetupTrait
{
    use X509AuthenticationCertificateFactoryTrait;

    public const INVALID_DEVICE_TYPE_NAME = 'TK800INVALID';
    public const INVALID_DEVICE_NAME = 'tk800invalid';
    public const AUTHENTICATION_CERTIFICATE_TYPE_NAME = 'Authentication';

    /**
     * Provides credentials to ApiClient.
     * Requires that setupDeviceUserState and setupDeviceTypeSecretState are called before.
     *
     * @param array $credentials Credentials array containing user/device secret info
     */
    public function provideCredentials(array $credentials): void
    {
        // Preparing credentials if possible, if not possible providing invalid credentials to allow negative tests
        if (isset($credentials['useDeviceSecret'])) {
            if ($credentials['useDeviceSecret']) {
                $username = $this->getProvidedEntityParameter(DeviceCredentialUseDeviceSecretModel::class, 'useDeviceSecretUsername');
                $password = $this->getProvidedEntityParameter(DeviceCredentialUseDeviceSecretModel::class, 'useDeviceSecretPassword');
            } else {
                $username = $this->getProvidedEntityParameter(DeviceCredentialUseUserModel::class, 'useUserUsername');
                $password = $this->getProvidedEntityParameter(DeviceCredentialUseUserModel::class, 'useUserPassword');
            }
        }

        // Providing invalid credentials if not set to allow negative tests, basic auth and digest auth requires credentials to be used
        $this->getApiClient()->provide(
            DeviceCredentialModel::class,
            [
                'username' => $username ?? 'InvalidUsername',
                'password' => $password ?? 'InvalidPassword',
            ]
        );
    }

    /**
     * Prepares Device Authentication User for tests.
     */
    public function setupDeviceUserState(DeviceType $deviceType, array $credentials): void
    {
        if (!isset($credentials['deviceUserState'])) {
            return;
        }

        $username = 'deviceUser';
        $password = 'devicePassword';

        // Added to allow tests to work if keep static connections is not used
        if (!self::useKeepStaticConnections()) {
            $this->removeUser($username);
        }

        switch ($credentials['deviceUserState']) {
            case DeviceUserState::VALID:
                $this->setupUserRoleState(
                    username: $username,
                    password: $password,
                    deviceType: $deviceType,
                    enabled: true,
                    roleDevice: true,
                    roleDeviceType: true
                );
                break;
            case DeviceUserState::INVALID_PASSWORD:
                $this->setupUserRoleState(
                    username: $username,
                    password: $password,
                    deviceType: $deviceType,
                    enabled: true,
                    roleDevice: true,
                    roleDeviceType: true
                );
                $password = 'INVALID_PASSWORD';
                break;
            case DeviceUserState::NOT_EXISTS:
                $this->setupUserRoleState(
                    username: $username,
                    password: $password,
                    deviceType: $deviceType,
                    enabled: true,
                    roleDevice: true,
                    roleDeviceType: true
                );
                $username = 'deviceUserNONEXISTS';
                break;
            case DeviceUserState::DISABLED:
                $this->setupUserRoleState(
                    username: $username,
                    password: $password,
                    deviceType: $deviceType,
                    enabled: false,
                    roleDevice: true,
                    roleDeviceType: true
                );
                break;
            case DeviceUserState::NO_ROLE_DEVICE:
                $this->setupUserRoleState(
                    username: $username,
                    password: $password,
                    deviceType: $deviceType,
                    enabled: true,
                    roleDevice: false,
                    roleDeviceType: true
                );
                break;
            case DeviceUserState::NO_DEVICE_TYPE_ROLE:
                $this->setupUserRoleState(
                    username: $username,
                    password: $password,
                    deviceType: $deviceType,
                    enabled: true,
                    roleDevice: true,
                    roleDeviceType: false
                );
                break;
            default:
                // Do nothing if null
                break;
        }

        $this->getApiClient()->provide(
            DeviceCredentialUseUserModel::class,
            [
                'useUserUsername' => $username,
                'useUserPassword' => $password,
            ]
        );
    }

    /**
     * Prepares User and UserDeviceType object for provided parameters.
     */
    public function setupUserRoleState(string $username, string $password, DeviceType $deviceType, bool $enabled = true, bool $roleDevice = true, bool $roleDeviceType = true)
    {
        $user = $this->createDeviceAuthenticationUser($username, $password);
        $this->assertNotNull($user);

        $user->setEnabled($enabled);
        $user->setRoleDevice($roleDevice);

        if (!$roleDeviceType) {
            $userDeviceType = $this->getRepository(UserDeviceType::class)->findOneBy(['deviceType' => $deviceType, 'user' => $user]);
            if ($userDeviceType) {
                $user->removeUserDeviceType($userDeviceType);
                $deviceType->removeUserDeviceType($userDeviceType);
                $this->getEntityManager()->persist($deviceType);
                $this->getEntityManager()->remove($userDeviceType);
            }
        } else {
            $userDeviceType = $this->getRepository(UserDeviceType::class)->findOneBy(['deviceType' => $deviceType, 'user' => $user]);
            if (!$userDeviceType) {
                $userDeviceType = new UserDeviceType();
                $userDeviceType->setUser($user);
                $userDeviceType->setDeviceType($deviceType);
                $userDeviceType->setUserRole(UserRole::DEVICE);
                $userDeviceType->setCreatedBy($user);
                $userDeviceType->setUpdatedBy($user);
                $user->addUserDeviceType($userDeviceType);
                $this->getEntityManager()->persist($userDeviceType);
            }
        }

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Creates a Device Authentication User entity.
     */
    protected function createDeviceAuthenticationUser(string $userName, string $password): User
    {
        $user = new User();
        $user->setUsername($userName);
        $user->setRoleDevice(true);
        $user->setEnabled(true);
        $user->setSalt(rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '='));
        $user->setPassword($this->getUserPasswordHasher()->hashPassword($user, $password));

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    /**
     * Removes a user entity by username.
     */
    public function removeUser(string $username): void
    {
        $user = $this->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($user) {
            $this->getEntityManager()->remove($user);
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Prepares DeviceTypeCertificate to the state required by test scenario.
     */
    public function setupDeviceTypeCertificateState(DeviceType $deviceType, array $credentials): void
    {
        if (!isset($credentials['certificateState'])) {
            return;
        }

        $certificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => self::AUTHENTICATION_CERTIFICATE_TYPE_NAME]);
        $this->assertNotNull($certificateType);

        if (DeviceCertificateState::NOT_IN_DEVICE_TYPE_CREDENTIAL !== $credentials['certificateState']) {
            $deviceType->setDeviceTypeCertificateTypeCredential($certificateType);
        } else {
            $deviceType->setDeviceTypeCertificateTypeCredential(null);
        }

        // Certificate should be setup even if not used as credential
        $deviceTypeCertificateType = $this->getRepository(DeviceTypeCertificateType::class)->findOneBy(['certificateType' => $certificateType, 'deviceType' => $deviceType]);
        if (!$deviceTypeCertificateType) {
            $deviceTypeCertificateType = new DeviceTypeCertificateType();
            $deviceTypeCertificateType->setDeviceType($deviceType);
            $deviceTypeCertificateType->setCertificateType($certificateType);
            $deviceType->addCertificateType($deviceTypeCertificateType);

            $this->getEntityManager()->persist($deviceTypeCertificateType);
            $this->getEntityManager()->persist($deviceTypeCertificateType->getDeviceType());
        }

        $this->getEntityManager()->persist($deviceType);
    }

    /**
     * Prepares DeviceCertificate to the state required by test scenario.
     */
    public function setupDeviceCertificateState(DeviceType $deviceType, array $credentials): void
    {
        if (!isset($credentials['certificateState'])) {
            $sslCertificate = $this->getRequestCertificate(DeviceCertificateState::VALID);
            $sslKey = $this->getRequestPrivateKey(DeviceCertificateState::VALID);
        } else {
            // No device to add certificate to
            if (!$this->hasProvidedEntityParameter(DeviceModel::class, 'id')) {
                return;
            }

            $device = $this->getProvidedDeviceEntity();

            $cert = $this->getSystemCertificate($credentials['certificateState']);
            $key = $this->getSystemPrivateKey($credentials['certificateState']);
            $ca = $this->getSystemCaCertificate($credentials['certificateState']);

            $this->setupDeviceAuthenticationCertificate($device, $key, $cert, $ca);

            $sslCertificate = $this->getRequestCertificate($credentials['certificateState']);
            $sslKey = $this->getRequestPrivateKey($credentials['certificateState']);
        }

        $this->getApiClient()->provide(
            DeviceCertificateModel::class,
            [
                'sslCertificate' => $sslCertificate,
                'sslKey' => $sslKey,
            ]
        );
    }

    /**
     * Sets up Device Authentication Certificate for a device.
     */
    public function setupDeviceAuthenticationCertificate(Device $device, ?string $key = null, ?string $certificate = null, ?string $ca = null): void
    {
        $certificateType = $this->getRepository(CertificateType::class)->findOneBy(['name' => self::AUTHENTICATION_CERTIFICATE_TYPE_NAME]);
        $this->assertNotNull($certificateType);

        $certificateObject = $this->getRepository(Certificate::class)->findOneBy(['device' => $device, 'certificateType' => $certificateType]);
        // It's ok that it doesn't exist
        if (!$certificateObject) {
            $certificateObject = new Certificate();
            $certificateObject->setDevice($device);
            $certificateObject->setCertificateType($certificateType);
        }

        if ($certificate) {
            $certificateObject->setCertificate($this->getEncryptionManager()->encrypt($certificate));
            $certificateData = openssl_x509_parse($certificate);
            $validTo = new \DateTime();
            $validTo->setTimestamp($certificateData['validTo_time_t']);

            $certificateObject->setCertificateValidTo($validTo);
            $certificateObject->setCertificateSubject($certificateData['subject']['CN']);
        }

        if ($key) {
            $certificateObject->setPrivateKey($this->getEncryptionManager()->encrypt($key));
        }

        if ($ca) {
            $certificateObject->setCertificateCa($this->getEncryptionManager()->encrypt($ca));
            $certificateData = openssl_x509_parse($ca);
            $certificateObject->setCertificateCaSubject($certificateData['subject']['CN']);
        }

        $certificateObject->setPkcsPrivateKeyPassword(null);

        $this->getEntityManager()->persist($certificateObject);
    }

    /**
     * Prepares DeviceTypeSecret to the state required by test scenario.
     */
    public function setupDeviceTypeSecretState(DeviceType $deviceType, array $credentials): void
    {
        if (!isset($credentials['deviceSecretState'])) {
            return;
        }

        $deviceSecretUsername = 'deviceSecretUser';
        switch ($credentials['deviceSecretState']) {
            case DeviceSecretState::VALID:
            case DeviceSecretState::INVALID_PASSWORD:
            case DeviceSecretState::INVALID_PASSWORD_SAME_AS_ENCRYPTED:
            case DeviceSecretState::EXPIRED:
            case DeviceSecretState::NOT_EXISTS:
                $this->createAndSetupDeviceTypeSecret(
                    deviceType: $deviceType,
                    deviceSecretName: $deviceSecretUsername,
                    autoValueRenewal: false
                );
                break;
            case DeviceSecretState::WILL_BE_RENEWED:
                $this->createAndSetupDeviceTypeSecret(
                    deviceType: $deviceType,
                    deviceSecretName: $deviceSecretUsername,
                    autoValueRenewal: true
                );
                break;
            case DeviceSecretState::NOT_IN_DEVICE_TYPE_CREDENTIAL:
                $this->createAndSetupDeviceTypeSecret(
                    deviceType: $deviceType,
                    deviceSecretName: $deviceSecretUsername,
                    autoValueRenewal: false
                );

                // This line will create new device secret which will be used instead of above one
                $this->createAndSetupDeviceTypeSecret(
                    deviceType: $deviceType,
                    deviceSecretName: 'deviceSecretUserNotInCredentials',
                    autoValueRenewal: false
                );

                break;
            case DeviceSecretState::DIFFERENT_DEVICE_TYPE:
                // Setting up deviceTypeSecret in tested deviceType
                $this->createAndSetupDeviceTypeSecret(
                    deviceType: $deviceType,
                    deviceSecretName: $deviceSecretUsername,
                    autoValueRenewal: false
                );

                // Setting up deviceTypeSecret in invalid deviceType (deviceSecret will only be created in invalid deviceType's device)
                $invalidDeviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => self::INVALID_DEVICE_TYPE_NAME]);
                $this->assertNotNull($invalidDeviceType);
                $this->createAndSetupDeviceTypeSecret(
                    deviceType: $invalidDeviceType,
                    deviceSecretName: $deviceSecretUsername,
                    autoValueRenewal: false
                );
                break;

            case DeviceSecretState::NAME_SAME_AS_USERNAME:
                $deviceSecretUsername = 'deviceUserName';
                $this->createAndSetupDeviceTypeSecret(
                    deviceType: $deviceType,
                    deviceSecretName: $deviceSecretUsername,
                    autoValueRenewal: false
                );
                break;
            default:
                break;
            // Do nothing if null
        }

        $this->getApiClient()->provide(
            DeviceCredentialUseDeviceSecretModel::class,
            [
                'useDeviceSecretUsername' => $deviceSecretUsername,
                'useDeviceSecretPassword' => 'INVALID PASSWORD', // Will be setup correctly in setupDeviceSecretState if needed
            ]
        );
    }

    /**
     * Helper method to create and set up a DeviceTypeSecret object.
     */
    public function createAndSetupDeviceTypeSecret(DeviceType $deviceType, string $deviceSecretName, bool $autoValueRenewal = false): void
    {
        $deviceTypeSecret = new DeviceTypeSecret();
        $deviceTypeSecret->setDeviceType($deviceType);
        $deviceTypeSecret->setName($deviceSecretName);
        if ($autoValueRenewal) {
            $deviceTypeSecret->setUseAsVariable(true);
            $deviceTypeSecret->setManualForceRenewal(true);
            $deviceTypeSecret->setSecretValueBehaviour(SecretValueBehaviour::RENEW);
        }

        $deviceType->setDeviceTypeSecretCredential($deviceTypeSecret);

        $this->getEntityManager()->persist($deviceTypeSecret);
        $this->getEntityManager()->persist($deviceTypeSecret->getDeviceType());
        $this->getEntityManager()->persist($deviceType);

        $this->getEntityManager()->flush();
    }

    /**
     * Prepares DeviceSecret to the state required by test scenario.
     */
    public function setupDeviceSecretState(DeviceType $deviceType, array $credentials): void
    {
        if (!isset($credentials['deviceSecretState'])) {
            return;
        }
        // No device to add secret to
        if (!$this->hasProvidedEntityParameter(DeviceModel::class, 'id')) {
            return;
        }

        $device = $this->getProvidedDeviceEntity();
        $deviceSecretUsername = $this->getProvidedEntityParameter(DeviceCredentialUseDeviceSecretModel::class, 'useDeviceSecretUsername');

        $password = 'INVALID_PASSWORD';
        switch ($credentials['deviceSecretState']) {
            case DeviceSecretState::NAME_SAME_AS_USERNAME:
            case DeviceSecretState::VALID:
                $password = $this->createAndSetupDeviceSecretByDeviceType($deviceType, $device);
                break;
            case DeviceSecretState::INVALID_PASSWORD:
                $value = $this->createAndSetupDeviceSecretByDeviceType($deviceType, $device);
                $password = $value.'INVALID';
                break;
            case DeviceSecretState::INVALID_PASSWORD_SAME_AS_ENCRYPTED:
                $value = $this->createAndSetupDeviceSecretByDeviceType($deviceType, $device);
                $this->getEntityManager()->persist($device->getDeviceType());
                $this->getEntityManager()->persist($device);
                $this->getEntityManager()->flush();
                $deviceTypeSecret = $deviceType->getDeviceTypeSecretCredential();
                $this->assertNotNull($deviceTypeSecret);
                $deviceSecret = $this->getRepository(DeviceSecret::class)->findOneBy(['device' => $device, 'deviceTypeSecret' => $deviceTypeSecret]);
                $password = $deviceSecret->getSecretValue();
                break;
            case DeviceSecretState::NOT_IN_DEVICE_TYPE_CREDENTIAL:
                $deviceTypeSecret = $this->getRepository(DeviceTypeSecret::class)->findOneBy(['deviceType' => $deviceType, 'name' => $deviceSecretUsername]);
                $this->assertNotNull($deviceTypeSecret);
                $password = $this->createAndSetupDeviceSecret($deviceTypeSecret, $device);
                break;
            case DeviceSecretState::DIFFERENT_DEVICE_TYPE:
                // Setting up deviceTypeSecret in invalid deviceType (deviceSecret will only be created in invalid deviceType's device)
                $invalidDeviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => self::INVALID_DEVICE_TYPE_NAME]);
                $this->assertNotNull($invalidDeviceType);
                $invalidDevice = $this->getRepository(Device::class)->findOneBy(['identifier' => self::INVALID_DEVICE_NAME, 'deviceType' => $invalidDeviceType]);
                $this->assertNotNull($invalidDevice);
                $password = $this->createAndSetupDeviceSecretByDeviceType($invalidDeviceType, $invalidDevice);
                break;
            case DeviceSecretState::NOT_EXISTS:
                $deviceTypeSecret = $deviceType->getDeviceTypeSecretCredential();
                // if not configured correctly it will fail
                $this->assertNotNull($deviceTypeSecret);
                $deviceSecret = $this->getRepository(DeviceSecret::class)->findOneBy(['device' => $device, 'deviceTypeSecret' => $deviceTypeSecret]);
                if ($deviceSecret) {
                    $this->getEntityManager()->remove($deviceSecret);
                }
                break;
            case DeviceSecretState::EXPIRED:
                $password = $this->createAndSetupDeviceSecretByDeviceType($deviceType, $device, null, '-1 day');
                break;
            case DeviceSecretState::WILL_BE_RENEWED:
                $password = $this->createAndSetupDeviceSecretByDeviceType($deviceType, $device, null, '-1 day');
                break;
            default:
                // Do nothing if null
                break;
        }

        $this->getApiClient()->provide(
            DeviceCredentialUseDeviceSecretModel::class,
            [
                'useDeviceSecretUsername' => $deviceSecretUsername,
                'useDeviceSecretPassword' => $password,
            ]
        );
    }

    /**
     * Helper method to create DeviceSecret object by DeviceType.
     */
    public function createAndSetupDeviceSecretByDeviceType(DeviceType $deviceType, Device $device, ?string $value = null, ?string $renewPeriod = null): string
    {
        $deviceTypeSecret = $deviceType->getDeviceTypeSecretCredential();
        // if not configured correctly it will fail
        $this->assertNotNull($deviceTypeSecret);

        return $this->createAndSetupDeviceSecret($deviceTypeSecret, $device, $value, $renewPeriod);
    }

    /**
     * Helper method to create DeviceSecret object.
     */
    public function createAndSetupDeviceSecret(DeviceTypeSecret $deviceTypeSecret, Device $device, ?string $value = null, ?string $renewedPeriod = null): string
    {
        $deviceSecret = $this->getRepository(DeviceSecret::class)->findOneBy(['device' => $device, 'deviceTypeSecret' => $deviceTypeSecret]);
        // It's ok that it doesn't exist
        if (!$deviceSecret) {
            $deviceSecret = new DeviceSecret();
            $deviceSecret->setDevice($device);
            $deviceSecret->setDeviceTypeSecret($deviceTypeSecret);
            $device->addDeviceSecret($deviceSecret);
        }

        $renewedAt = null;
        if ($renewedPeriod) {
            $renewedAt = new \DateTime();
            $renewedAt->modify($renewedPeriod);
        }
        $deviceSecret->setRenewedAt($renewedAt);

        if (!$value) {
            $value = $this->getRandomString();
        }
        $deviceSecret->setSecretValue($value);

        $this->getDeviceSecretManager()->encryptDeviceSecret($deviceSecret);

        $this->getEntityManager()->persist($deviceSecret);

        return $value;
    }
}
