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

class VpnConnectedClientsModel
{
    /**
     * Common name.
     */
    private ?string $commonName = null;

    /**
     * Vpn IP address.
     */
    private ?string $vpnIp = null;

    /**
     * Bytes received.
     */
    private ?int $bytesReceived = 0;

    /**
     * Bytes send.
     */
    private ?int $bytesSent = 0;

    public function getCommonName(): ?string
    {
        return $this->commonName;
    }

    public function setCommonName(?string $commonName)
    {
        $this->commonName = $commonName;
    }

    public function getVpnIp(): ?string
    {
        return $this->vpnIp;
    }

    public function setVpnIp(?string $vpnIp)
    {
        $this->vpnIp = $vpnIp;
    }

    public function getBytesReceived(): ?int
    {
        return $this->bytesReceived;
    }

    public function setBytesReceived(?int $bytesReceived)
    {
        $this->bytesReceived = $bytesReceived;
    }

    public function getBytesSent(): ?int
    {
        return $this->bytesSent;
    }

    public function setBytesSent(?int $bytesSent)
    {
        $this->bytesSent = $bytesSent;
    }
}
