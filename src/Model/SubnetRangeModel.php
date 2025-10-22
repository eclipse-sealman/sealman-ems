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

use App\Enum\VpnSubnetType;

/**
 * Detailed subnet range information.
 */
class SubnetRangeModel
{
    /**
     * Subnet in canonical form e.g. 172.16.0.0/16.
     */
    private ?string $subnet = null;

    /**
     * Subnet first ip as long.
     */
    private ?int $subnetStartIp = null;

    /**
     * Subnet last ip as long.
     */
    private ?int $subnetEndIp = null;

    /**
     * Subnet size (number of hosts).
     */
    private ?int $subnetSize = null;

    /**
     * Subnet cidr e.g. 16 as in 172.16.0.0/16.
     */
    private ?int $subnetCidr = null;

    /**
     * Range in canonical form e.g. 172.16.0.3-172.16.0.53.
     */
    private ?string $range = null;

    /**
     * Range first ip as long.
     */
    private ?int $rangeStartIp = null;

    /**
     * Range last ip as long.
     */
    private ?int $rangeEndIp = null;

    /**
     * Range size (number of hosts).
     */
    private ?int $rangeSize = null;

    /**
     * VPN Subnet type (Device VPN IP, Technician VPN IP, Devices VPN Virtual IP).
     */
    private ?VpnSubnetType $type = null;

    public function getSubnet(): ?string
    {
        return $this->subnet;
    }

    public function setSubnet(?string $subnet)
    {
        $this->subnet = $subnet;
    }

    public function getSubnetStartIp(): ?int
    {
        return $this->subnetStartIp;
    }

    public function setSubnetStartIp(?int $subnetStartIp)
    {
        $this->subnetStartIp = $subnetStartIp;
    }

    public function getSubnetEndIp(): ?int
    {
        return $this->subnetEndIp;
    }

    public function setSubnetEndIp(?int $subnetEndIp)
    {
        $this->subnetEndIp = $subnetEndIp;
    }

    public function getSubnetSize(): ?int
    {
        return $this->subnetSize;
    }

    public function setSubnetSize(?int $subnetSize)
    {
        $this->subnetSize = $subnetSize;
    }

    public function getSubnetCidr(): ?int
    {
        return $this->subnetCidr;
    }

    public function setSubnetCidr(?int $subnetCidr)
    {
        $this->subnetCidr = $subnetCidr;
    }

    public function getRange(): ?string
    {
        return $this->range;
    }

    public function setRange(?string $range)
    {
        $this->range = $range;
    }

    public function getRangeStartIp(): ?int
    {
        return $this->rangeStartIp;
    }

    public function setRangeStartIp(?int $rangeStartIp)
    {
        $this->rangeStartIp = $rangeStartIp;
    }

    public function getRangeEndIp(): ?int
    {
        return $this->rangeEndIp;
    }

    public function setRangeEndIp(?int $rangeEndIp)
    {
        $this->rangeEndIp = $rangeEndIp;
    }

    public function getRangeSize(): ?int
    {
        return $this->rangeSize;
    }

    public function setRangeSize(?int $rangeSize)
    {
        $this->rangeSize = $rangeSize;
    }

    public function getType(): ?VpnSubnetType
    {
        return $this->type;
    }

    public function setType(?VpnSubnetType $type)
    {
        $this->type = $type;
    }
}
