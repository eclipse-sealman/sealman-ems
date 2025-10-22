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
 * Used as a source of information by App\Validator\Constraints\DeviceEndpointDeviceLockValidator with group 'deviceEndpointDevice:lock'.
 *
 * Validator requires object of this class to be set in a App\Entity\DeviceEndpointDevice::$lock.
 *
 * Any opened connection to endpoint device should lock changing of virtualIpHostPart.
 * This will prevent reassignment of virtualIp while connection is opened which is not supported by our system.
 */
class DeviceEndpointDeviceLock
{
    /**
     * Should virtualIpHostPart be locked?
     */
    private bool $lockVirtualIpHostPart;

    /**
     * Current virtualIpHostPart.
     */
    private ?int $virtualIpHostPart;

    public function __construct(bool $lockVirtualIpHostPart, ?int $virtualIpHostPart)
    {
        $this->lockVirtualIpHostPart = $lockVirtualIpHostPart;
        $this->virtualIpHostPart = $virtualIpHostPart;
    }

    public function getLockVirtualIpHostPart(): bool
    {
        return $this->lockVirtualIpHostPart;
    }

    public function setLockVirtualIpHostPart(bool $lockVirtualIpHostPart)
    {
        $this->lockVirtualIpHostPart = $lockVirtualIpHostPart;
    }

    public function getVirtualIpHostPart(): ?int
    {
        return $this->virtualIpHostPart;
    }

    public function setVirtualIpHostPart(?int $virtualIpHostPart)
    {
        $this->virtualIpHostPart = $virtualIpHostPart;
    }
}
