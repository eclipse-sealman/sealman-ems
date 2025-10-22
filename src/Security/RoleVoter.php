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

namespace App\Security;

use App\Entity\DeviceType;
use App\Entity\User;
use App\Entity\UserDeviceType;
use App\Enum\AuthenticationMethod;
use App\Enum\CredentialsSource;
use App\Enum\UserRole;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\PasswordManagerTrait;
use App\Service\Helper\RequestStackTrait;
use App\Service\Helper\TotpManagerTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoleVoter extends Voter
{
    use RequestStackTrait;
    use EntityManagerTrait;
    use PasswordManagerTrait;
    use TotpManagerTrait;
    use ConfigurationManagerTrait;

    protected function supports($attribute, $subject): bool
    {
        if (in_array($attribute, [
            'ROLE_ADMIN',
            'ROLE_ADMIN_VPN',
            'ROLE_ADMIN_SCEP',
            'ROLE_SMARTEMS',
            'ROLE_VPN',
            'ROLE_VPN_ENDPOINTDEVICES',
            // Read more about ROLE_DOCS_* in hasRoleDocs()
            'ROLE_DOCS_ADMIN',
            'ROLE_DOCS_ADMIN_VPN',
            'ROLE_DOCS_ADMIN_SCEP',
            'ROLE_DOCS_SMARTEMS',
            'ROLE_DOCS_VPN',
            'ROLE_DEVICE',
            'ROLE_RADIUSUSER',
            'ROLE_SSOUSER',
            'ROLE_CHANGEPASSWORDREQUIRED',
            'ROLE_TOTPREQUIRED',
            'ROLE_UPLOAD',
        ])) {
            return true;
        }

        if (str_starts_with($attribute, 'ROLE_DEVICE_TYPE_')) {
            return true;
        }

        return false;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (str_starts_with($attribute, 'ROLE_DEVICE_TYPE_')) {
            $deviceTypeId = str_replace('ROLE_DEVICE_TYPE_', '', $attribute);

            $deviceType = $this->getRepository(DeviceType::class)->find($deviceTypeId);
            if (!$deviceType) {
                return false;
            }

            if (!$deviceType->getIsAvailable()) {
                return false;
            }

            if (AuthenticationMethod::NONE == $deviceType->getAuthenticationMethod()) {
                return true;
            }

            if (!$user instanceof User) {
                return false;
            }

            if (in_array($deviceType->getAuthenticationMethod(), [AuthenticationMethod::BASIC, AuthenticationMethod::DIGEST])) {
                if ($user->getRoleDeviceSecretCredential() && in_array($deviceType->getCredentialsSource(), [CredentialsSource::SECRET, CredentialsSource::USER_IF_SECRET_MISSING, CredentialsSource::BOTH])) {
                    return true;
                }
            }

            if (AuthenticationMethod::X509 == $deviceType->getAuthenticationMethod()) {
                if ($user->getRoleDeviceX509Credential() && $deviceType->getDeviceTypeCertificateTypeCredential()) {
                    return true;
                }
            }

            if (!$user->getRoleDevice()) {
                return false;
            }

            $userDeviceType = $this->getRepository(UserDeviceType::class)->findOneBy([
                'user' => $user,
                'deviceType' => $deviceType,
                'userRole' => UserRole::DEVICE,
            ]);

            return null !== $userDeviceType;
        }

        if (!$user instanceof User) {
            if (str_starts_with($attribute, 'ROLE_DOCS_')) {
                return $this->hasRoleDocs($attribute);
            }

            return false;
        }

        if ('ROLE_USER' === $attribute) {
            return true;
        }

        if ('ROLE_RADIUSUSER' === $attribute) {
            return $user->getRadiusUser();
        }

        if ('ROLE_SSOUSER' === $attribute) {
            return $user->getSsoUser();
        }

        $isPasswordExpired = $this->passwordManager->isPasswordExpired($user);
        // When password change is required user gets ROLE_CHANGEPASSWORDREQUIRED
        if ('ROLE_CHANGEPASSWORDREQUIRED' === $attribute && $isPasswordExpired) {
            return true;
        }

        // When password change is required user will not have any other roles
        if ($isPasswordExpired) {
            return false;
        }

        $isTotpRequired = $user->getTotpRequired() && $this->totpManager->isUserTotpEnabled($user);
        // When TOTP is required user gets ROLE_TOTPREQUIRED
        if ('ROLE_TOTPREQUIRED' === $attribute && $isTotpRequired) {
            return true;
        }

        // When TOTP is required user will not have any other roles
        if ($isTotpRequired) {
            return false;
        }

        // Even in maintenance mode devices should be able to connect - access limitations is done on controller level to provide correct response
        if ('ROLE_DEVICE' === $attribute) {
            return $user->getRoleDevice() || $user->getRoleDeviceSecretCredential() || $user->getRoleDeviceX509Credential();
        }

        // When maintenance mode is enabled only Administrators can access the system
        if ($this->configurationManager->isMaintenanceModeEnabled() && !$user->getRoleAdmin()) {
            return false;
        }

        // * Note! Please keep ROLE_X roles in line with ROLE_DOCS_X roles to have correct OpenAPI documentation
        if ('ROLE_ADMIN' === $attribute) {
            return $user->getRoleAdmin();
        }

        if ('ROLE_ADMIN_VPN' === $attribute) {
            return $user->getRoleAdmin() && !$this->configurationManager->isVpnSecuritySuiteBlocked();
        }

        if ('ROLE_ADMIN_SCEP' === $attribute) {
            return $user->getRoleAdmin() && !$this->configurationManager->isScepBlocked();
        }

        if ('ROLE_SMARTEMS' === $attribute) {
            return $user->getRoleSmartems();
        }

        if ('ROLE_VPN' === $attribute) {
            return $user->getRoleVpn() && !$this->configurationManager->isVpnSecuritySuiteBlocked();
        }

        if ('ROLE_VPN_ENDPOINTDEVICES' === $attribute) {
            return $user->getRoleVpn() && $user->getRoleVpnEndpointDevices() && !$this->configurationManager->isVpnSecuritySuiteBlocked();
        }

        if ('ROLE_UPLOAD' === $attribute) {
            return $user->getRoleSmartems() || $user->getRoleAdmin();
        }

        return false;
    }

    /**
     * Function assigns ROLE_DOCS_X roles based on current route.
     * They are mirroring normal ROLE_X and are used for generating OpenAPI documentation in complex forms i.e. App\Form\DeviceEditType.
     * Those roles have no use use case beyond that and should NOT have access to any parts of the system.
     */
    protected function hasRoleDocs(string $role): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        switch ($role) {
            case 'ROLE_DOCS_ADMIN_VPN':
                if ($this->configurationManager->isVpnSecuritySuiteBlocked()) {
                    return false;
                }
                break;
            case 'ROLE_DOCS_ADMIN_SCEP':
                if ($this->configurationManager->isScepBlocked()) {
                    return false;
                }
                break;
            case 'ROLE_DOCS_VPN':
                if ($this->configurationManager->isVpnSecuritySuiteBlocked()) {
                    return false;
                }
                break;
        }

        $supportedRoutes = [];

        switch ($role) {
            case 'ROLE_DOCS_ADMIN':
            case 'ROLE_DOCS_ADMIN_VPN':
            case 'ROLE_DOCS_ADMIN_SCEP':
            case 'ROLE_DOCS_VPN':
                $supportedRoutes[] = '/web/doc/vpnsecuritysuite';
                $supportedRoutes[] = '/web/doc/smartemsvpnsecuritysuite';
                break;
            case 'ROLE_DOCS_SMARTEMS':
                $supportedRoutes[] = '/web/doc/smartems';
                $supportedRoutes[] = '/web/doc/smartemsvpnsecuritysuite';
                break;
            default:
                return false;
        }

        // Extend routes with .yml suffix
        $supportedRoutesKeys = array_keys($supportedRoutes);
        foreach ($supportedRoutesKeys as $key) {
            $supportedRoutes[] = $supportedRoutes[$key].'.yaml';
        }

        if (!in_array($request->getPathInfo(), $supportedRoutes)) {
            return false;
        }

        return true;
    }
}
