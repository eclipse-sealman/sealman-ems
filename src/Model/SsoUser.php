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

/**
 * User information used in single sign-on (SSO).
 */
class SsoUser
{
    /**
     * Username is used as unique identifier. May not be human friendly and it might be a random unique string from IdP.
     */
    private ?string $username = null;

    /**
     * Name is used as human friendly repesentation for this SSO user.
     */
    private ?string $name = null;

    private ?string $sessionId = null;

    private ?bool $roleAdmin = false;

    private ?bool $roleSmartems = false;

    private ?bool $roleVpn = false;

    private ?bool $roleVpnEndpointDevices = false;

    private array $accessTags = [];

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username)
    {
        $this->username = $username;
    }

    public function getRoleAdmin(): ?bool
    {
        return $this->roleAdmin;
    }

    public function setRoleAdmin(?bool $roleAdmin)
    {
        $this->roleAdmin = $roleAdmin;
    }

    public function getRoleSmartems(): ?bool
    {
        return $this->roleSmartems;
    }

    public function setRoleSmartems(?bool $roleSmartems)
    {
        $this->roleSmartems = $roleSmartems;
    }

    public function getRoleVpn(): ?bool
    {
        return $this->roleVpn;
    }

    public function setRoleVpn(?bool $roleVpn)
    {
        $this->roleVpn = $roleVpn;
    }

    public function getAccessTags(): array
    {
        return $this->accessTags;
    }

    public function setAccessTags(array $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId)
    {
        $this->sessionId = $sessionId;
    }

    public function getRoleVpnEndpointDevices(): ?bool
    {
        return $this->roleVpnEndpointDevices;
    }

    public function setRoleVpnEndpointDevices(?bool $roleVpnEndpointDevices)
    {
        $this->roleVpnEndpointDevices = $roleVpnEndpointDevices;
    }
}
