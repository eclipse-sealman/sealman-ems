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

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class MicrosoftOidcAuthorizationState
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Microsoft OIDC authorization state.
     */
    #[ORM\Column(type: Types::STRING)]
    private ?string $state = null;

    /**
     * Microsoft OIDC PKCE code.
     */
    #[ORM\Column(type: Types::STRING)]
    private ?string $pkceCode = null;

    /**
     * Expire date.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $expireAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state)
    {
        $this->state = $state;
    }

    public function getExpireAt(): ?\DateTime
    {
        return $this->expireAt;
    }

    public function setExpireAt(?\DateTime $expireAt)
    {
        $this->expireAt = $expireAt;
    }

    public function getPkceCode(): ?string
    {
        return $this->pkceCode;
    }

    public function setPkceCode(?string $pkceCode)
    {
        $this->pkceCode = $pkceCode;
    }
}
