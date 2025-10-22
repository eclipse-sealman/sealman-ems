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

namespace App\Model;

use OpenApi\Attributes as OA;

/**
 * Model of AuthenticationData used when returning information about authenticated user.
 * *Note!* This is only for easier definition of REST API Documentation. Not used otherwise in application.
 */
class AuthenticationData
{
    private int $accessTokenTtl = 3000;

    private string $lastLoginAt = '';

    private string $refreshToken = '';

    private int $refreshTokenExpiration = 3000;

    // enum values are taken from AuthenticationManager::getSerializableRoles()
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: [
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
    ]))]
    private array $roles = [];

    private int $sessionTimeout = 9000;

    #[OA\Property(description: 'JWT token (use it with prefix "Bearer" e.g. "Bearer eyX1fr...")')]
    private string $token = '';

    private string $username = '';

    #[OA\Property(description: 'TOTP QR Code URL. Present only once when logging in for the first time after two-factor authentication has been enabled')]
    private ?string $totpUrl = null;

    #[OA\Property(description: 'TOTP Secret. Present only once when logging in for the first time after two-factor authentication has been enabled')]
    private ?string $totpSecret = null;

    public function getAccessTokenTtl(): int
    {
        return $this->accessTokenTtl;
    }

    public function setAccessTokenTtl(int $accessTokenTtl)
    {
        $this->accessTokenTtl = $accessTokenTtl;
    }

    public function getLastLoginAt(): string
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(string $lastLoginAt)
    {
        $this->lastLoginAt = $lastLoginAt;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public function getRefreshTokenExpiration(): int
    {
        return $this->refreshTokenExpiration;
    }

    public function setRefreshTokenExpiration(int $refreshTokenExpiration)
    {
        $this->refreshTokenExpiration = $refreshTokenExpiration;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function getSessionTimeout(): int
    {
        return $this->sessionTimeout;
    }

    public function setSessionTimeout(int $sessionTimeout)
    {
        $this->sessionTimeout = $sessionTimeout;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token)
    {
        $this->token = $token;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    public function getTotpUrl(): ?string
    {
        return $this->totpUrl;
    }

    public function setTotpUrl(?string $totpUrl)
    {
        $this->totpUrl = $totpUrl;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret)
    {
        $this->totpSecret = $totpSecret;
    }
}
