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
 * Used as a source of information by App\Validator\Constraints\DeviceLockValidator with group 'device:lock'.
 *
 * Validator requires object of this class to be set in a App\Entity\Device::$lock.
 *
 * Any opened connection to a device or any endpoint device (within this device) should lock changing virtualSubnetCidr.
 * This will prevent reassignment of virtualIp while connection is opened which is not supported by our system.
 *
 * Any opened connection to an endpoint device (within this device) should prevent it from being removed.
 * This will prevent issues with removal of an endpoint device with opened connection when using a collection (when editing a device).
 */
class DeviceLock
{
    /**
     * Should virtualSubnetCidr be locked?
     */
    private bool $lockVirtualSubnetCidr;

    /**
     * Current virtualSubnetCidr.
     */
    private ?int $virtualSubnetCidr;

    /**
     * List of endpoint device IDs that should be locked (cannot be removed).
     */
    private array $lockedEndpointDeviceIds;

    public function __construct(bool $lockVirtualSubnetCidr, ?int $virtualSubnetCidr, array $lockedEndpointDeviceIds)
    {
        $this->lockVirtualSubnetCidr = $lockVirtualSubnetCidr;
        $this->virtualSubnetCidr = $virtualSubnetCidr;
        $this->lockedEndpointDeviceIds = $lockedEndpointDeviceIds;
    }

    public function getLockVirtualSubnetCidr(): bool
    {
        return $this->lockVirtualSubnetCidr;
    }

    public function setLockedEndpointDeviceIds(array $lockedEndpointDeviceIds)
    {
        $this->lockedEndpointDeviceIds = $lockedEndpointDeviceIds;
    }

    public function getVirtualSubnetCidr(): ?int
    {
        return $this->virtualSubnetCidr;
    }

    public function setVirtualSubnetCidr(?int $virtualSubnetCidr)
    {
        $this->virtualSubnetCidr = $virtualSubnetCidr;
    }

    public function setLockVirtualSubnetCidr(bool $lockVirtualSubnetCidr)
    {
        $this->lockVirtualSubnetCidr = $lockVirtualSubnetCidr;
    }

    public function getLockedEndpointDeviceIds(): array
    {
        return $this->lockedEndpointDeviceIds;
    }
}
