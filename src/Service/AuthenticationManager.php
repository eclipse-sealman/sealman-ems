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
use App\Entity\UserLoginAttempt;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\RequestStackTrait;
use App\Service\Helper\TotpManagerTrait;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Request\Extractor\ExtractorInterface;
use Symfony\Contracts\Service\Attribute\Required;

class AuthenticationManager
{
    use AuthorizationCheckerTrait;
    use TotpManagerTrait;
    use EntityManagerTrait;
    use RequestStackTrait;
    use ConfigurationManagerTrait;

    #[Required]
    public string $refreshTokenParameterName;

    #[Required]
    public string $refreshTokenReturnExpirationParameterName;

    #[Required]
    public int $sessionTimeout;

    #[Required]
    public int $accessTokenTtl;

    /**
     * @var RefreshTokenManagerInterface
     */
    protected $refreshTokenManager;

    /**
     * @var ExtractorInterface
     */
    protected $extractor;

    #[Required]
    public function setRefreshTokenManager(RefreshTokenManagerInterface $refreshTokenManager)
    {
        $this->refreshTokenManager = $refreshTokenManager;
    }

    #[Required]
    public function setExtractor(ExtractorInterface $extractor)
    {
        $this->extractor = $extractor;
    }

    public function extendAuthenticationData(User $user, array $data): array
    {
        $data['sessionTimeout'] = $this->sessionTimeout;
        $data['accessTokenTtl'] = $this->accessTokenTtl;

        // Need to fix refresh token expiration passed by default
        // This is probably a bug within the package
        $refreshTokenString = $data[$this->refreshTokenParameterName] ?? null;
        if ($refreshTokenString) {
            $refreshToken = $this->refreshTokenManager->get($refreshTokenString);

            if ($refreshToken) {
                $data[$this->refreshTokenReturnExpirationParameterName] = $refreshToken->getValid()->getTimestamp();
            }
        }

        $data['username'] = $user->getUserIdentifier();
        $data['representation'] = $user->getRepresentation();
        $data['lastLoginAt'] = $user->getLastLoginAt() ? $user->getLastLoginAt()->format('c') : null;
        $data['roles'] = $this->getSerializedRoles();

        if ($this->isGranted('ROLE_TOTPREQUIRED') && !$user->getTotpSecret()) {
            $secret = $this->totpManager->generateSecretKey($user);

            $user->setTotpSecret($secret);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $data['totpUrl'] = $this->totpManager->getUserSecretUrl($user);
            $data['totpSecret'] = $user->getTotpSecret();
        }

        return $data;
    }

    public function registerLoginAttempt(bool $loginValid, bool $totpValid, string $username, ?User $user = null): void
    {
        if (!$user) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        }

        $request = $this->requestStack->getCurrentRequest();

        $loginAttempt = new UserLoginAttempt();
        $loginAttempt->setUser($user);
        $loginAttempt->setUsername($username);
        $loginAttempt->setLoginValid($loginValid);
        $loginAttempt->setTotpValid($totpValid);
        $loginAttempt->setRemoteHost($request->getClientIp());

        $this->entityManager->persist($loginAttempt);
        $this->entityManager->flush();
    }

    /**
     * Note: This function does not execute entityManager->flush().
     */
    public function resetLoginAttempts(User $user): void
    {
        $user->setTooManyFailedLoginAttempts(false);
        $user->setFailedLoginAttempts(0);
        $user->setFailedLoginAttemptsAt(null);

        $this->entityManager->persist($user);
    }

    /**
     * Note: This function does not execute entityManager->flush().
     */
    public function increaseFailedLoginAttempts(User $user): void
    {
        if (!$this->isFailedLoginAttemptsAvailableForUser($user)) {
            return;
        }

        $user->setFailedLoginAttempts($user->getFailedLoginAttempts() + 1);
        if ($user->getFailedLoginAttempts() >= $this->getConfiguration()->getFailedLoginAttemptsLimit()) {
            $user->setTooManyFailedLoginAttempts(true);
        }

        if (!$user->getTooManyFailedLoginAttempts() || !$user->getFailedLoginAttemptsAt()) {
            $user->setFailedLoginAttemptsAt(new \DateTime());
        }

        $this->entityManager->persist($user);
    }

    public function hasUserTooManyFailedLoginAttemptsSet(User $user): bool
    {
        if (!$this->isFailedLoginAttemptsAvailableForUser($user)) {
            return false;
        }

        return $user->getTooManyFailedLoginAttempts();
    }

    public function isUserDuringTooManyFailedLoginAttemptsDuration(User $user): bool
    {
        if (!$this->hasUserTooManyFailedLoginAttemptsSet($user)) {
            return false;
        }

        $failedLoginAttemptsAt = $user->getFailedLoginAttemptsAt();
        if (!$failedLoginAttemptsAt) {
            // When $failedLoginAttemptsAt is missing (should not happen) lets assume it is expired
            return false;
        }

        $expiresAt = clone $failedLoginAttemptsAt;
        $expiresAt->modify($this->getConfiguration()->getFailedLoginAttemptsDisablingDuration());

        return $expiresAt >= new \DateTime();
    }

    public function isFailedLoginAttemptsEnabled(): bool
    {
        return $this->getConfiguration()->getFailedLoginAttemptsEnabled();
    }

    public function isFailedLoginAttemptsAvailableForUser(User $user): bool
    {
        if (!$this->isFailedLoginAttemptsEnabled()) {
            return false;
        }

        return $user->getRoleAdmin() || $user->getRoleSmartems() || $user->getRoleVpn();
    }

    /**
     * Please be aware that copy of those serializable roles is used in App\Model\AuthenticationData->roles REST API documentation definition.
     */
    public static function getSerializableRoles(): array
    {
        return [
            'ROLE_USER',
            'ROLE_CHANGEPASSWORDREQUIRED',
            'ROLE_RADIUSUSER',
            'ROLE_SSOUSER',
            'ROLE_TOTPREQUIRED',
            'ROLE_ADMIN',
            'ROLE_ADMIN_VPN',
            'ROLE_ADMIN_SCEP',
            'ROLE_SMARTEMS',
            'ROLE_VPN',
            'ROLE_VPN_ENDPOINTDEVICES',
        ];
    }

    public function getSerializedRoles(): array
    {
        $serializableRoles = self::getSerializableRoles();

        $serializedRoles = [];
        foreach ($serializableRoles as $role) {
            if ($this->isGranted($role)) {
                $serializedRoles[] = $role;
            }
        }

        return $serializedRoles;
    }
}
