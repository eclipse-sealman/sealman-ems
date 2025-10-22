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

use App\Entity\User;
use App\Entity\VpnConnection;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Model used when returning information about connection status.
 */
class ConnectionStatus
{
    #[Groups('user:openVpnPublic')]
    private ?User $user = null;

    #[Groups('user:openVpnPublic')]
    #[OA\Property(type: 'array', items: new OA\Items(ref: new NA\Model(type: VpnConnection::class)))]
    private array $connections = [];

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function getConnections(): array
    {
        return $this->connections;
    }

    public function setConnections(array $connections)
    {
        $this->connections = $connections;
    }
}
