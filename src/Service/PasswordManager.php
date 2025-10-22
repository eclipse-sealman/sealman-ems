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
use App\Entity\UserOldPassword;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Service\Attribute\Required;

class PasswordManager
{
    use EntityManagerTrait;
    use ConfigurationManagerTrait;

    /**
     * @var UserPasswordHasherInterface
     */
    protected $userPasswordHasher;

    #[Required]
    public function setUserPasswordHasher(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function isPasswordExpired(User $user): bool
    {
        // Passwords might expire only for WebUI users
        if (!$user->getRoleAdmin() && !$user->getRoleSmartems() && !$user->getRoleVpn()) {
            return false;
        }

        if ($user->getRadiusUser()) {
            return false;
        }

        if ($user->getSsoUser()) {
            return false;
        }

        if ($user->getDisablePasswordExpire()) {
            return false;
        }

        $passwordExpireDays = $this->getConfiguration()->getPasswordExpireDays();
        if ($passwordExpireDays <= 0) {
            return false;
        }

        $passwordUpdatedAt = $user->getPasswordUpdatedAt();
        if (!$passwordUpdatedAt) {
            return true;
        }

        $now = new \DateTime();
        $passwordExpireAt = clone $passwordUpdatedAt;
        $passwordExpireAt->modify('+'.$passwordExpireDays.' days');

        if ($now > $passwordExpireAt) {
            return true;
        }

        return false;
    }

    public function changePassword(User $user, string $newPlainPassword): void
    {
        // User is not yet in database. Skip saving old password
        if ($user->getId()) {
            $oldPassword = new UserOldPassword();
            $oldPassword->setUser($user);
            $oldPassword->setPassword($user->getPassword());
            $this->entityManager->persist($oldPassword);
        }

        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $newPlainPassword);
        $user->setPassword($hashedPassword);
        $user->setPasswordUpdatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function isPasswordCurrentlyUsed(User $user, string $plainPassword): bool
    {
        return $this->userPasswordHasher->isPasswordValid($user, $plainPassword);
    }

    public function isPasswordRecentlyUsed(User $user, string $plainPassword): bool
    {
        // User is not yet in database. Skip password recently used check
        if (!$user->getId()) {
            return false;
        }

        $passwordBlockReuseOldPasswordCount = $this->getConfiguration()->getPasswordBlockReuseOldPasswordCount();
        if ($passwordBlockReuseOldPasswordCount <= 0) {
            return false;
        }

        $queryBuilder = $this->entityManager->getRepository(UserOldPassword::class)->createQueryBuilder('uop');
        $queryBuilder->andWhere('uop.user = :user');
        $queryBuilder->setParameter(':user', $user);
        $queryBuilder->addOrderBy('uop.createdAt', 'DESC');
        $queryBuilder->setMaxResults($passwordBlockReuseOldPasswordCount);

        $oldPasswords = $queryBuilder->getQuery()->getResult();

        foreach ($oldPasswords as $oldPassword) {
            if ($this->userPasswordHasher->isPasswordValid($oldPassword, $plainPassword)) {
                return true;
            }
        }

        return false;
    }
}
