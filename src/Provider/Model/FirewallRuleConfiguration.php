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

use App\Provider\Enum\Protocol;

class FirewallRuleConfiguration
{
    /**
     * Source IP.
     */
    private string $sourceIp;

    /**
     * Source netmask.
     */
    private int $sourceNetmask = 32;

    /**
     * Source begin port.
     */
    private ?string $sourceBeginPort;

    /**
     * Source end port.
     */
    private ?string $sourceEndPort;

    /**
     * Destination IP.
     */
    private string $destinationIp;

    /**
     * Destination netmask.
     */
    private int $destinationNetmask = 32;

    /**
     * Destination begin port.
     */
    private ?string $destinationBeginPort;

    /**
     * Destination end port.
     */
    private ?string $destinationEndPort;

    /**
     * Inet protocol.
     */
    private Protocol $protocol = Protocol::ANY;

    /**
     * Existing rule index (in remote system eg. OpnSense rule index).
     */
    private ?string $ruleIndex;

    /**
     * Existing rule identifier (in remote system eg. OpnSense rule md5).
     */
    private ?string $ruleIdentifier;

    public function __construct(string $sourceIp, string $destinationIp, int $sourceNetmask = 32, int $destinationNetmask = 32)
    {
        $this->sourceIp = $sourceIp;
        $this->destinationIp = $destinationIp;
        $this->sourceNetmask = $sourceNetmask;
        $this->destinationNetmask = $destinationNetmask;
    }

    public function getSourceIp(): string
    {
        return $this->sourceIp;
    }

    public function setSourceIp(string $sourceIp)
    {
        $this->sourceIp = $sourceIp;
    }

    public function getSourceNetmask(): int
    {
        return $this->sourceNetmask;
    }

    public function setSourceNetmask(int $sourceNetmask)
    {
        $this->sourceNetmask = $sourceNetmask;
    }

    public function getSourceBeginPort(): ?string
    {
        return $this->sourceBeginPort;
    }

    public function setSourceBeginPort(?string $sourceBeginPort)
    {
        $this->sourceBeginPort = $sourceBeginPort;
    }

    public function getSourceEndPort(): ?string
    {
        return $this->sourceEndPort;
    }

    public function setSourceEndPort(?string $sourceEndPort)
    {
        $this->sourceEndPort = $sourceEndPort;
    }

    public function getDestinationIp(): string
    {
        return $this->destinationIp;
    }

    public function setDestinationIp(string $destinationIp)
    {
        $this->destinationIp = $destinationIp;
    }

    public function getDestinationNetmask(): int
    {
        return $this->destinationNetmask;
    }

    public function setDestinationNetmask(int $destinationNetmask)
    {
        $this->destinationNetmask = $destinationNetmask;
    }

    public function getDestinationBeginPort(): ?string
    {
        return $this->destinationBeginPort;
    }

    public function setDestinationBeginPort(?string $destinationBeginPort)
    {
        $this->destinationBeginPort = $destinationBeginPort;
    }

    public function getDestinationEndPort(): ?string
    {
        return $this->destinationEndPort;
    }

    public function setDestinationEndPort(?string $destinationEndPort)
    {
        $this->destinationEndPort = $destinationEndPort;
    }

    public function getProtocol(): Protocol
    {
        return $this->protocol;
    }

    public function setProtocol(Protocol $protocol)
    {
        $this->protocol = $protocol;
    }

    public function getRuleIndex(): ?string
    {
        return $this->ruleIndex;
    }

    public function setRuleIndex(?string $ruleIndex)
    {
        $this->ruleIndex = $ruleIndex;
    }

    public function getRuleIdentifier(): ?string
    {
        return $this->ruleIdentifier;
    }

    public function setRuleIdentifier(?string $ruleIdentifier)
    {
        $this->ruleIdentifier = $ruleIdentifier;
    }
}
