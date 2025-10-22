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

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

// We need a custom serialization for false values due to serialization up to v2.5.2. Said version had custom serialization and when value has been false it was not send at all (omitted). False value is not interpreted properly on via VCC (v1.0.5) and is treated as true (lack of value is treated as false).
class VpnContainerClientResponseModel extends ResponseModel
{
    /**
     * VCC name.
     */
    #[Groups(['vpnContainerClient:register', 'vpnContainerClient:configuration'])]
    private ?string $name = null;

    /**
     *  VCC UUID.
     */
    #[Groups(['vpnContainerClient:register', 'vpnContainerClient:configuration'])]
    private ?string $uuid = null;

    /**
     * Configuration content.
     */
    #[Groups(['vpnContainerClient:configuration'])]
    private mixed $configuration = null;

    /**
     *  Error information.
     */
    #[Groups(['vpnContainerClient:register', 'vpnContainerClient:configuration'])]
    private ?string $error = null;

    /**
     *  Clear UUID command.
     */
    // Value has to be not serialized for backwards compatibilty with VPN Container Client (1.0.5)
    private bool $clearUuid = false;

    /**
     *  UnRegister command.
     */
    // Value has to be not serialized for backwards compatibilty with VPN Container Client (1.0.5)
    private bool $unregister = false;

    #[Groups(['vpnContainerClient:register', 'vpnContainerClient:configuration'])]
    #[SerializedName('clearUuid')]
    public function getSerializedClearUuid(): ?bool
    {
        if ($this->clearUuid) {
            return true;
        }

        return null;
    }

    #[Groups(['vpnContainerClient:register', 'vpnContainerClient:configuration'])]
    #[SerializedName('unregister')]
    public function getSerializedUnregister(): ?bool
    {
        if ($this->unregister) {
            return true;
        }

        return null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getConfiguration(): mixed
    {
        return $this->configuration;
    }

    public function setConfiguration(mixed $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error)
    {
        $this->error = $error;
    }

    public function getClearUuid(): bool
    {
        return $this->clearUuid;
    }

    public function setClearUuid(bool $clearUuid)
    {
        $this->clearUuid = $clearUuid;
        // Value has to be not serialized for backwards compatibilty with VPN Container Client (1.0.5)
        if (false === $clearUuid) {
            $this->clearUuid = null;
        }
    }

    public function getUnregister(): bool
    {
        return $this->unregister;
    }

    public function setUnregister(bool $unregister)
    {
        $this->unregister = $unregister;
        // Value has to be not serialized for backwards compatibilty with VPN Container Client (1.0.5)
        if (false === $unregister) {
            $this->unregister = null;
        }
    }
}
