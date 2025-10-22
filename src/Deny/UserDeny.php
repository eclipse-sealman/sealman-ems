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

namespace App\Deny;

use App\Entity\User;
use App\Service\Helper\AuthenticationManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\TotpManagerTrait;
use App\Service\Helper\UserTrait;

class UserDeny extends AbstractApiDuplicateCertificateTypeObjectDeny implements VpnConfigDenyInterface
{
    use UserTrait;
    use TotpManagerTrait;
    use ConfigurationManagerTrait;
    use VpnConfigDenyTrait;
    use AuthenticationManagerTrait;

    public const ENABLE = 'enable';
    public const DISABLE = 'disable';
    public const CHANGE_PASSWORD = 'changePassword';
    public const RESET_TOTP_SECRET = 'resetTotpSecret';
    public const RESET_LOGIN_ATTEMPTS = 'resetLoginAttempts';

    public function editDeny(User $object): ?string
    {
        if ($object->getRadiusUser()) {
            return 'notAvailableForRadiusUser';
        }

        if ($object->getSsoUser()) {
            return 'notAvailableForSsoUser';
        }

        return null;
    }

    public function changePasswordDeny(User $object): ?string
    {
        if ($object->getRadiusUser()) {
            return 'notAvailableForRadiusUser';
        }

        if ($object->getSsoUser()) {
            return 'notAvailableForSsoUser';
        }

        if ($this->getUser() instanceof User && $this->getUser()->getId() === $object->getId()) {
            return 'currentlyLoggedIn';
        }

        return null;
    }

    public function resetTotpSecretDeny(User $object): ?string
    {
        if (!$this->getConfiguration()->getTotpEnabled()) {
            return 'totpDisabled';
        }

        if ($object->getRadiusUser()) {
            return 'notAvailableForRadiusUser';
        }

        if ($object->getSsoUser()) {
            return 'notAvailableForSsoUser';
        }

        if (!$object->getTotpSecret()) {
            return 'totpSecretEmpty';
        }

        return null;
    }

    public function resetLoginAttemptsDeny(User $object): ?string
    {
        if (!$this->authenticationManager->isFailedLoginAttemptsEnabled()) {
            return 'failedLoginAttemptsDisabled';
        }

        if (!$this->authenticationManager->isFailedLoginAttemptsAvailableForUser($object)) {
            return 'failedLoginAttemptsNotAvailableForUser';
        }

        if (!$object->getTooManyFailedLoginAttempts()) {
            return 'tooManyFailedLoginAttemptsFalse';
        }

        if ($object->getRadiusUser()) {
            return 'notAvailableForRadiusUser';
        }

        if ($object->getSsoUser()) {
            return 'notAvailableForSsoUser';
        }

        return null;
    }

    public function disableDeny(User $object): ?string
    {
        if (!$object->getEnabled()) {
            return 'alreadyDisabled';
        }

        if ($this->getUser() == $object) {
            return 'cannotDisableYourself';
        }

        if ($object->getRadiusUser()) {
            return 'notAvailableForRadiusUser';
        }

        if ($object->getSsoUser()) {
            return 'notAvailableForSsoUser';
        }

        return null;
    }

    public function enableDeny(User $object): ?string
    {
        if ($object->getEnabled()) {
            return 'alreadyEnabled';
        }

        if ($object->getRadiusUser()) {
            return 'notAvailableForRadiusUser';
        }

        if ($object->getSsoUser()) {
            return 'notAvailableForSsoUser';
        }

        return null;
    }

    public function deleteDeny(User $object): ?string
    {
        if ($this->getUser() == $object) {
            return 'cannotDeleteYourself';
        }

        return null;
    }
}
