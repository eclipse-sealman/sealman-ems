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

namespace App\Service;

use App\Entity\User;
use App\Enum\TotpWindow;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use PragmaRX\Google2FA\Google2FA;

class TotpManager
{
    use ConfigurationManagerTrait;
    use EntityManagerTrait;

    /**
     * @var ?Google2FA
     */
    protected $google2FA;

    public function getGoogle2Fa(): Google2FA
    {
        if (!$this->google2FA) {
            $google2FA = new Google2FA();
            $google2FA->setOneTimePasswordLength($this->getConfiguration()->getTotpTokenLength());
            $google2FA->setKeyRegeneration($this->getConfiguration()->getTotpKeyRegeneration());
            $google2FA->setAlgorithm($this->getConfiguration()->getTotpAlgorithm()->value);
            $google2FA->setWindow($this->getTotpWindow());

            $this->google2FA = $google2FA;
        }

        return $this->google2FA;
    }

    protected function getTotpWindow(): int
    {
        return match ($this->getConfiguration()->getTotpWindow()) {
            TotpWindow::INTERVAL_1 => 0,
            TotpWindow::INTERVAL_3 => 1,
            TotpWindow::INTERVAL_5 => 2,
            default => 0,
        };
    }

    public function isUserTotpEnabled(User $user): bool
    {
        if (!$user->getRoleAdmin() && !$user->getRoleSmartems() && !$user->getRoleVpn()) {
            return false;
        }

        if ($user->getRadiusUser()) {
            return false;
        }

        if ($user->getSsoUser()) {
            return false;
        }

        if (!$user->getTotpEnabled()) {
            return false;
        }

        return $this->getConfiguration()->getTotpEnabled();
    }

    public function validateTotp(User $user, string $key): bool
    {
        if (!$user->getTotpSecret()) {
            return false;
        }

        return false !== $this->getGoogle2Fa()->verifyKey($user->getTotpSecret(), $key) ? true : false;
    }

    public function generateSecretKey(): string
    {
        return $this->getGoogle2Fa()->generateSecretKey($this->getConfiguration()->getTotpSecretLength());
    }

    public function getUserSecretUrl(User $user): ?string
    {
        if (!$user->getTotpSecret()) {
            return null;
        }

        return $this->getGoogle2Fa()->getQRCodeUrl(
            'SEALMAN',
            $user->getUsername(),
            $user->getTotpSecret()
        );
    }

    public function isTotpSecretGenerated(): bool
    {
        $queryBuilder = $this->getRepository(User::class)->createQueryBuilder('u');
        $queryBuilder->select('COUNT(u.id)');
        $queryBuilder->andWhere('u.totpSecret IS NOT NULL');
        $totpSecretUserCount = $queryBuilder->getQuery()->getSingleScalarResult();

        return $totpSecretUserCount > 0;
    }
}
