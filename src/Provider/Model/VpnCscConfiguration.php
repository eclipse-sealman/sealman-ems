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

namespace App\Provider\Model;

class VpnCscConfiguration
{
    /**
     * VPN Server name.
     */
    private string $cscServerName;

    /**
     * VPN CSC common name.
     */
    private string $cscCommonName;

    /**
     * VPN Tunnel network.
     */
    private string $tunnelNetwork;

    /**
     * VPN Remote networks.
     */
    private array $remoteNetworks = [];

    public function getCommaDelimitedRemoteNetworks(): string
    {
        return implode(',', $this->getRemoteNetworks());
    }

    public function hasRemoteNetworks(): bool
    {
        return count($this->getRemoteNetworks()) > 0;
    }

    public function __construct(string $cscServerName, string $cscCommonName, string $tunnelNetwork, array $remoteNetworks = [])
    {
        $this->cscServerName = $cscServerName;
        $this->cscCommonName = $cscCommonName;
        $this->tunnelNetwork = $tunnelNetwork;
        $this->remoteNetworks = $remoteNetworks;
    }

    public function getCscServerName(): string
    {
        return $this->cscServerName;
    }

    public function setCscServerName(string $cscServerName)
    {
        $this->cscServerName = $cscServerName;
    }

    public function getCscCommonName(): string
    {
        return $this->cscCommonName;
    }

    public function setCscCommonName(string $cscCommonName)
    {
        $this->cscCommonName = $cscCommonName;
    }

    public function getTunnelNetwork(): string
    {
        return $this->tunnelNetwork;
    }

    public function setTunnelNetwork(string $tunnelNetwork)
    {
        $this->tunnelNetwork = $tunnelNetwork;
    }

    public function getRemoteNetworks(): array
    {
        return $this->remoteNetworks;
    }

    public function setRemoteNetworks(array $remoteNetworks)
    {
        $this->remoteNetworks = $remoteNetworks;
    }
}
