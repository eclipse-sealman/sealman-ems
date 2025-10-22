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

namespace App\Service;

use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\VpnConnection;
use App\Model\DeviceEndpointDeviceLock;
use App\Model\DeviceLock;
use App\Service\Helper\EntityManagerTrait;

/**
 * Explanation of lock logic in:
 * - App\Model\DeviceLock
 * - App\Model\DeviceEndpointDeviceLock.
 */
class LockManager
{
    use EntityManagerTrait;

    public function getDeviceLock(Device $device): DeviceLock
    {
        $lockVirtualSubnetCidr = false;
        $virtualSubnetCidr = $device->getVirtualSubnetCidr();
        $lockedEndpointDeviceIds = $this->getEndpointDeviceIdsWithVpnConnections($device);

        $hasDeviceVpnConnections = $this->hasDeviceVpnConnections($device);
        if ($hasDeviceVpnConnections || count($lockedEndpointDeviceIds) > 0) {
            $lockVirtualSubnetCidr = true;
        }

        return new DeviceLock($lockVirtualSubnetCidr, $virtualSubnetCidr, $lockedEndpointDeviceIds);
    }

    public function getEndpointDeviceLock(DeviceEndpointDevice $endpointDevice): DeviceEndpointDeviceLock
    {
        $lockVirtualIpHostPart = $this->hasEndpointDeviceVpnConnections($endpointDevice);
        $virtualIpHostPart = $endpointDevice->getVirtualIpHostPart();

        return new DeviceEndpointDeviceLock($lockVirtualIpHostPart, $virtualIpHostPart);
    }

    public function shouldLockTemplateApplyEndpointDevices(Device $device): bool
    {
        if ($this->hasDeviceVpnConnections($device)) {
            return true;
        }

        $lockedEndpointDeviceIds = $this->getEndpointDeviceIdsWithVpnConnections($device);
        if (count($lockedEndpointDeviceIds) > 0) {
            return true;
        }

        return false;
    }

    protected function hasDeviceVpnConnections(Device $device): bool
    {
        $count = $this->getRepository(VpnConnection::class)->count([
            'device' => $device,
        ]);

        return $count > 0;
    }

    protected function getEndpointDeviceIdsWithVpnConnections(Device $device): array
    {
        $queryBuilder = $this->getRepository(DeviceEndpointDevice::class)->createQueryBuilder('ed');
        $queryBuilder->select('ed.id');
        $queryBuilder->andWhere('ed.device = :device');
        $queryBuilder->setParameter('device', $device);
        $endpointDeviceIds = $queryBuilder->getQuery()->getSingleColumnResult();

        $queryBuilder = $this->getRepository(VpnConnection::class)->createQueryBuilder('c');
        $queryBuilder->select('ed.id');
        $queryBuilder->leftJoin('c.endpointDevice', 'ed');
        $queryBuilder->andWhere('ed.id IN (:endpointDeviceIds)');
        $queryBuilder->setParameter('endpointDeviceIds', $endpointDeviceIds);
        $endpointDeviceIdsWithConnections = $queryBuilder->getQuery()->getSingleColumnResult();

        return $endpointDeviceIdsWithConnections;
    }

    protected function hasEndpointDeviceVpnConnections(DeviceEndpointDevice $endpointDevice): bool
    {
        $count = $this->getRepository(VpnConnection::class)->count([
            'endpointDevice' => $endpointDevice,
        ]);

        return $count > 0;
    }
}
