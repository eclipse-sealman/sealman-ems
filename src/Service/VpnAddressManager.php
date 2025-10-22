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

use App\Entity\Configuration;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\User;
use App\Entity\VpnSubnet;
use App\Enum\VpnSubnetType;
use App\Model\SubnetRangeModel;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Trait\Ipv4HelperTrait;
use App\Service\Trait\SubnetRangeModelTrait;
use Doctrine\Common\Collections\Collection;

class VpnAddressManager
{
    use EntityManagerTrait;
    use ConfigurationManagerTrait;
    use VpnLogManagerTrait;
    use Ipv4HelperTrait;
    use SubnetRangeModelTrait;

    public const DEVICE_VIRTUAL_ADDRESS_NO = 0;

    public function setupVpnAddresses(Device|User|DeviceEndpointDevice $target): Device|User|DeviceEndpointDevice
    {
        if ($target instanceof DeviceEndpointDevice) {
            if (!$target->getDevice()->getVpnIp()) {
                // do nothing if parent device is not setup correctly
                return $target;
            }

            if (!$target->getVirtualSubnet()) {
                $this->vpnLogManager->createLogCritical('log.vpn.virtualSubnetMissing', endpointDevice: $target);

                return $target;
            }

            if (!$target->getVirtualIpHostPart()) {
                $this->vpnLogManager->createLogCritical('log.vpn.virtualIpHostPartMissing', endpointDevice: $target);

                return $target;
            }

            $ip = $this->getAddressInSubnet($target->getVirtualIpHostPart(), $target->getVirtualSubnet());
            if (!$ip) {
                $this->vpnLogManager->createLogCritical('log.vpn.virtualIpMissing', endpointDevice: $target);

                return $target;
            }

            // $ip is expected virtualIp
            if ($target->getVirtualIp() !== $ip) {
                $target->setVirtualIp($ip);
                $target->setVirtualIpSortable(ip2long($ip));
                $this->vpnLogManager->createLogInfo('log.vpn.virtualIpSet', ['virtualIp' => $ip], endpointDevice: $target);
            }

            return $target;
        }

        if (!$target->getVpnIp()) {
            if ($target instanceof Device && !$target->getDeviceType()->getIsVpnAvailable()) {
                return $target;
            }

            if ($target instanceof User) {
                $vpnSubnetType = VpnSubnetType::TECHNICIAN_VPN_IP;
            } else {
                $vpnSubnetType = VpnSubnetType::DEVICE_VPN_IP;
            }
            $subnet = $this->getSubnet(1, $vpnSubnetType);

            // for easier logging - not to use target parameter
            $logUser = null;
            $logDevice = null;
            if ($target instanceof Device) {
                $logDevice = $target;
            }
            if ($target instanceof User) {
                $logUser = $target;
            }

            if (!$subnet) {
                $this->vpnLogManager->createLogCritical('log.vpn.vpnIpNotAvailable', device: $logDevice, user: $logUser);

                return $target;
            }
            list($ip, $cidr) = explode('/', $subnet);
            if (!$ip) {
                $this->vpnLogManager->createLogCritical('log.vpn.vpnIpMissing', device: $logDevice, user: $logUser);

                return $target;
            }

            $target->setVpnIp($ip);
            $target->setVpnIpSortable(ip2long($ip));
            $this->vpnLogManager->createLogInfo('log.vpn.virtualIpSet', ['virtualIp' => $ip], device: $logDevice, user: $logUser);
        }

        if ($target instanceof Device) {
            if (!$target->getDeviceType()->getIsEndpointDevicesAvailable()) {
                return $target;
            }

            if ($target->getVirtualSubnetIp() && $target->getVirtualSubnet()) {
                // Subnet size has changed - remove current subnet, it will be set in later if
                list(, $cidr) = explode('/', $target->getVirtualSubnet());
                $cidrInt = intval($cidr);
                if ($target->getVirtualSubnetCidr() !== $cidrInt) {
                    $this->addVirtualIpLongSubnet($target->getVirtualSubnetIpSortable(), $cidrInt);

                    $target->setVirtualSubnetIp(null);
                    $target->setVirtualSubnetIpSortable(null);
                    $target->setVirtualSubnet(null);
                    $target->setVirtualIp(null);
                    $target->setVirtualIpSortable(null);

                    foreach ($target->getEndpointDevices() as $endpointDevice) {
                        if ($endpointDevice->getVirtualIp()) {
                            $endpointDevice->setVirtualIp(null);
                            $endpointDevice->setVirtualIpSortable(null);
                        }
                    }
                }
            }

            if (!$target->getVirtualSubnetIp()) {
                $subnet = $this->getSubnetByCidr($target->getVirtualSubnetCidr(), VpnSubnetType::DEVICE_VIRTUAL_IP);

                if (!$subnet) {
                    $this->vpnLogManager->createLogCritical('log.vpn.virtualSubnetNotAvailable', ['cidr' => $target->getVirtualSubnetCidr()], device: $target);

                    return $target;
                }

                list($ip, $cidr) = explode('/', $subnet);

                if (!$ip) {
                    $this->vpnLogManager->createLogCritical('log.vpn.virtualIpMissing', device: $target);

                    return $target;
                }

                $target->setVirtualSubnetIp($ip);
                $target->setVirtualSubnetIpSortable(ip2long($ip));
                $target->setVirtualSubnet($subnet);

                $this->vpnLogManager->createLogInfo('log.vpn.virtualSubnetSet', ['virtualSubnet' => $subnet], device: $target);
            }
            // is this correct condition for all devices - do all devices use virtual network - maybe they should? or not?
            if (!$target->getVirtualIp()) {
                if (!$target->getVirtualSubnet()) {
                    $this->vpnLogManager->createLogCritical('log.vpn.virtualSubnetMissing', device: $target);

                    return $target;
                }

                $ip = $this->getAddressInSubnet(self::DEVICE_VIRTUAL_ADDRESS_NO, $target->getVirtualSubnet());
                if (!$ip) {
                    $this->vpnLogManager->createLogCritical('log.vpn.virtualIpMissing', device: $target);

                    return $target;
                }

                $target->setVirtualIp($ip);
                $target->setVirtualIpSortable(ip2long($ip));

                $this->vpnLogManager->createLogInfo('log.vpn.virtualIpSet', ['virtualIp' => $ip], device: $target);
            }

            if (!$target->getVirtualSubnet()) {
                $this->vpnLogManager->createLogCritical('log.vpn.virtualSubnetMissing', device: $target);

                return $target;
            }

            foreach ($target->getEndpointDevices() as $endpointDevice) {
                if (!$endpointDevice->getVirtualIpHostPart()) {
                    $this->vpnLogManager->createLogCritical('log.vpn.virtualIpHostPartMissing', endpointDevice: $endpointDevice);

                    return $target;
                }

                $ip = $this->getAddressInSubnet($endpointDevice->getVirtualIpHostPart(), $target->getVirtualSubnet());
                if (!$ip) {
                    $this->vpnLogManager->createLogCritical('log.vpn.virtualIpMissing', endpointDevice: $endpointDevice);

                    return $target;
                }

                // $ip is expected virtualIp
                if ($endpointDevice->getVirtualIp() !== $ip) {
                    $endpointDevice->setVirtualIp($ip);
                    $endpointDevice->setVirtualIpSortable(ip2long($ip));

                    $this->vpnLogManager->createLogInfo('log.vpn.virtualIpSet', ['virtualIp' => $ip], endpointDevice: $endpointDevice);
                }
            }
        }

        return $target;
    }

    public function removeVpnAddresses(Device|User|DeviceEndpointDevice $target): Device|User|DeviceEndpointDevice
    {
        if ($target instanceof DeviceEndpointDevice) {
            $target->setVirtualIp(null);
            $target->setVirtualIpSortable(null);

            $this->vpnLogManager->createLogInfo('log.vpn.vpnIpRemoved', endpointDevice: $target);

            return $target;
        }

        // for easier logging - not to use target parameter
        $logUser = null;
        $logDevice = null;
        if ($target instanceof Device) {
            $logDevice = $target;
        }
        if ($target instanceof User) {
            $logUser = $target;
        }

        if ($target->getVpnIp()) {
            $this->addVirtualIpLongSubnet($target->getVpnIpSortable(), 32);

            $target->setVpnIp(null);
            $target->setVpnIpSortable(null);

            $this->vpnLogManager->createLogInfo('log.vpn.vpnIpRemoved', device: $logDevice, user: $logUser);
        }

        if ($target instanceof Device) {
            if (!$target->getDeviceType()->getIsVpnAvailable()) {
                return $target;
            }
            if ($target->getVirtualSubnetIp()) {
                $this->addVirtualIpLongSubnet($target->getVirtualSubnetIpSortable(), $target->getVirtualSubnetCidr());

                $target->setVirtualSubnetIp(null);
                $target->setVirtualSubnetIpSortable(null);
                $target->setVirtualSubnet(null);
                $target->setVirtualIp(null);
                $target->setVirtualIpSortable(null);

                foreach ($target->getEndpointDevices() as $endpointDevice) {
                    if ($endpointDevice->getVirtualIp()) {
                        $endpointDevice->setVirtualIp(null);
                        $endpointDevice->setVirtualIpSortable(null);
                    }
                }

                $this->vpnLogManager->createLogInfo('log.vpn.vpnVirtualAddressesRemoved', device: $target);
            }
        }

        return $target;
    }

    public function getTechniciansVpnNetworks(): array
    {
        $networks = $this->getConfiguration()->getTechniciansVpnNetworks();
        if (!$networks) {
            return [];
        }

        return explode(',', $networks);
    }

    public function getDevicesVpnNetworks(): array
    {
        $networks = $this->getConfiguration()->getDevicesVpnNetworks();
        if (!$networks) {
            return [];
        }

        return explode(',', $networks);
    }

    public function getDevicesVirtualVpnNetworks(): array
    {
        $networks = $this->getConfiguration()->getDevicesVirtualVpnNetworks();
        if (!$networks) {
            return [];
        }

        return explode(',', $networks);
    }

    public function getAllVpnNetworks(): array
    {
        return \array_merge(
            $this->getDevicesVirtualVpnNetworks(),
            $this->getDevicesVpnNetworks(),
            $this->getTechniciansVpnNetworks()
        );
    }

    // This method is intented only for internal use in Configuration fixtures - no check are executed
    // Method just adds subnet/ranges configuration from Configuration - assuming it is valid - not checks are executed
    public function addConfigurationSubnets(Configuration $configuration): void
    {
        $this->addSubnet(VpnSubnetType::DEVICE_VPN_IP, $configuration->getDevicesVpnNetworks(), $configuration->getDevicesVpnNetworksRanges());
        $this->addSubnet(VpnSubnetType::TECHNICIAN_VPN_IP, $configuration->getTechniciansVpnNetworks(), $configuration->getTechniciansVpnNetworksRanges());
        $this->addSubnet(VpnSubnetType::DEVICE_VIRTUAL_IP, $configuration->getDevicesVirtualVpnNetworks(), $configuration->getDevicesVirtualVpnNetworksRanges());
    }

    // Finds subnet of specific size in set network - **THIS FUNCTION WILL REMOVE RETURNED SUBNET FROM POOL - USE WITH CAUTION**
    public function getSubnet(int $size, VpnSubnetType $vpnSubnetType): ?string
    {
        if (!$this->isSizeValid($size)) {
            $this->vpnLogManager->createLogCritical('log.vpn.invalidSubnetSize', ['size' => $size]);

            return null;
        }

        $cidr = $this->sizeToCidr($size);

        return $this->getSubnetByCidr($cidr, $vpnSubnetType);
    }

    public function findNetwork(int $ipLong, int $cidr, ?string $forceSubnet = null): ?string
    {
        if (!$this->isCidrValid($cidr)) {
            $this->vpnLogManager->createLogCritical('log.vpn.invalidSubnetCidr', ['cidr' => $cidr]);

            return null;
        }

        $vpnSubnets = $this->getAllVpnNetworks();
        if ($forceSubnet) {
            $vpnSubnets = [$forceSubnet];
        }
        $network = null;
        foreach ($vpnSubnets as $vpnSubnet) {
            list($vpnSubnetNetwork, $vpnSubnetCidr) = explode('/', $vpnSubnet);

            $vpnSubnetNetwork = ip2long($vpnSubnetNetwork);
            $vpnSubnetCidr = intval($vpnSubnetCidr);
            $vpnSubnetSize = $this->cidrToSize($vpnSubnetCidr);
            $subnetSize = $this->cidrToSize($cidr);
            if ($ipLong >= $vpnSubnetNetwork && $ipLong + $subnetSize <= $vpnSubnetNetwork + $vpnSubnetSize) {
                return $vpnSubnet;
            }
        }

        return null;
    }

    private function findVpnSubnetType(string $network): ?VpnSubnetType
    {
        foreach ($this->getDevicesVpnNetworks() as $configuredNetwork) {
            if ($configuredNetwork === $network) {
                return VpnSubnetType::DEVICE_VPN_IP;
            }
        }

        foreach ($this->getDevicesVirtualVpnNetworks() as $configuredNetwork) {
            if ($configuredNetwork === $network) {
                return VpnSubnetType::DEVICE_VIRTUAL_IP;
            }
        }

        foreach ($this->getTechniciansVpnNetworks() as $configuredNetwork) {
            if ($configuredNetwork === $network) {
                return VpnSubnetType::TECHNICIAN_VPN_IP;
            }
        }

        return null;
    }

    public function processConfigurationSubnetsChange(Configuration $previousConfiguration, Configuration $configuration): void
    {
        if (
            $previousConfiguration->getDevicesVpnNetworks() != $configuration->getDevicesVpnNetworks() ||
            $previousConfiguration->getDevicesVpnNetworksRanges() != $configuration->getDevicesVpnNetworksRanges()
        ) {
            $ranges = $this->processSubnetsChange(
                VpnSubnetType::DEVICE_VPN_IP,
                $previousConfiguration->getDevicesVpnNetworks(),
                $previousConfiguration->getDevicesVpnNetworksRanges(),
                $configuration->getDevicesVpnNetworks(),
                $configuration->getDevicesVpnNetworksRanges()
            );
            $this->handleRangesChange(VpnSubnetType::DEVICE_VPN_IP, $ranges);
        }

        if (
            $previousConfiguration->getTechniciansVpnNetworks() != $configuration->getTechniciansVpnNetworks() ||
            $previousConfiguration->getTechniciansVpnNetworksRanges() != $configuration->getTechniciansVpnNetworksRanges()
        ) {
            $ranges = $this->processSubnetsChange(
                VpnSubnetType::TECHNICIAN_VPN_IP,
                $previousConfiguration->getTechniciansVpnNetworks(),
                $previousConfiguration->getTechniciansVpnNetworksRanges(),
                $configuration->getTechniciansVpnNetworks(),
                $configuration->getTechniciansVpnNetworksRanges()
            );
            $this->handleRangesChange(VpnSubnetType::TECHNICIAN_VPN_IP, $ranges);
        }

        if (
            $previousConfiguration->getDevicesVirtualVpnNetworks() != $configuration->getDevicesVirtualVpnNetworks() ||
            $previousConfiguration->getDevicesVirtualVpnNetworksRanges() != $configuration->getDevicesVirtualVpnNetworksRanges()
        ) {
            $ranges = $this->processSubnetsChange(
                VpnSubnetType::DEVICE_VIRTUAL_IP,
                $previousConfiguration->getDevicesVirtualVpnNetworks(),
                $previousConfiguration->getDevicesVirtualVpnNetworksRanges(),
                $configuration->getDevicesVirtualVpnNetworks(),
                $configuration->getDevicesVirtualVpnNetworksRanges()
            );
            $this->handleRangesChange(VpnSubnetType::DEVICE_VIRTUAL_IP, $ranges);
        }
    }

    /**
     * Method handles changes in networks and ranges
     * Method accepts array with (from processSubnetsChange)
     * [
     *    'rangesToAdd' => [], // array of SubnetRangeModel to be added to address pool
     *    'rangesToRemove' => [], // array of SubnetRangeModel to be remove from address pool
     *    'rangesToUpdate' => [], // array of SubnetRangeModel to be updated with new subnet
     * ].
     */
    private function handleRangesChange(VpnSubnetType $vpnSubnetType, array $ranges): void
    {
        if (isset($ranges['rangesToRemove'])) {
            $this->removeVpnAddressesByRanges($ranges['rangesToRemove']);
        }
        if (isset($ranges['rangesToUpdate'])) {
            $this->updateVpnAddressesByRanges($ranges['rangesToUpdate']);
        }
        if (isset($ranges['rangesToAdd'])) {
            $this->addVpnAddressesByRanges($vpnSubnetType, $ranges['rangesToAdd']);
        }
    }

    private function removeVpnAddressesByRanges(array $ranges): void
    {
        foreach ($ranges as $range) {
            // Find VpnSubnets which are fully contained in range and remove
            $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
            $query->delete(VpnSubnet::class, 'vs');
            $query->andWhere('vs.ipLong >= :startIp');
            $query->setParameter('startIp', $range->getRangeStartIp());
            $query->andWhere('vs.ipLong + vs.size -1 <= :endIp');
            $query->setParameter('endIp', $range->getRangeEndIp());
            $query->andWhere('vs.type = :type');
            $query->setParameter('type', $range->getType());
            $query->getQuery()->execute();

            $this->entityManager->flush();

            // Find VpnSubnets that contains startIp (might not be found if removed in step before if VpnSubnet started exactly at startIp)
            $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
            $query->andWhere('vs.ipLong <= :startIp');
            $query->andWhere('vs.ipLong + vs.size -1 >= :startIp');
            $query->setParameter('startIp', $range->getRangeStartIp());
            $query->andWhere('vs.type = :type');
            $query->setParameter('type', $range->getType());
            $foundSubnet = $query->getQuery()->getOneOrNullResult();

            if ($foundSubnet) {
                // divide subnet until required range can be removed
                $this->removeRangeFromVpnSubnet($range, $foundSubnet);
                $this->entityManager->flush();
            }

            // Find VpnSubnets that contains endIp (might not be found if removed in step before if VpnSubnet ended exactly at endIp)
            $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
            $query->andWhere('vs.ipLong <= :endIp');
            $query->andWhere('vs.ipLong + vs.size -1 >= :endIp');
            $query->setParameter('endIp', $range->getRangeEndIp());
            $query->andWhere('vs.type = :type');
            $query->setParameter('type', $range->getType());
            $foundSubnet = $query->getQuery()->getOneOrNullResult();

            if ($foundSubnet) {
                // divide subnet until required range can be removed
                $this->removeRangeFromVpnSubnet($range, $foundSubnet);
                $this->entityManager->flush();
            }
        }
    }

    // Method divides vpnSubnet and removes needed sub-vpnSubnets until only vpnSubnets in range are left
    private function removeRangeFromVpnSubnet(SubnetRangeModel $range, VpnSubnet $subnet): void
    {
        // whole subnet is to be removed
        if ($this->isVpnSubnetContainedInRange($range, $subnet)) {
            $this->entityManager->remove($subnet);
            $this->entityManager->flush();

            return;
        }

        // part of subnet is to be removed
        if ($this->isRangeOverlapVpnSubnet($range, $subnet)) {
            if ($subnet->getCidr() + 1 > 32) {
                return;
            }
            // divide subnet in half and do removeRangeFromVpnSubnet on both halfs

            $subnetLow = new VpnSubnet();
            $subnetLow->setNetwork($subnet->getNetwork());
            $subnetLow->setCidr($subnet->getCidr() + 1);
            $subnetLow->setSize($subnet->getSize() >> 1); // divide by 2
            $subnetLow->setIp($subnet->getIp());
            $subnetLow->setIpLong($subnet->getIpLong());
            $subnetLow->setType($subnet->getType());

            $this->entityManager->persist($subnetLow);

            $subnetHigh = new VpnSubnet();
            $subnetHigh->setNetwork($subnet->getNetwork());
            $subnetHigh->setCidr($subnet->getCidr() + 1);
            $subnetHigh->setSize($subnet->getSize() >> 1); // divide by 2
            $subnetHigh->setIpLong($subnet->getIpLong() + $subnetHigh->getSize());
            $subnetHigh->setIp(long2ip($subnetHigh->getIpLong()));
            $subnetHigh->setType($subnet->getType());

            $this->entityManager->persist($subnetHigh);

            $this->entityManager->remove($subnet);

            $this->removeRangeFromVpnSubnet($range, $subnetLow);
            $this->removeRangeFromVpnSubnet($range, $subnetHigh);
        }

        // else whole subnet is to be kept
    }

    private function addVpnAddressesByRanges(VpnSubnetType $vpnSubnetType, array $ranges): void
    {
        foreach ($ranges as $range) {
            // Add vpnSubnets that will fill whole range
            $this->addSubnetRange($vpnSubnetType, $range->getSubnet(), $range->getRangeStartIp(), $range->getRangeEndIp());

            // Try to merge first and last vpnSubnet in range (other should not be possible anyway)
            // If others become possible to merge recurence will merge them

            $this->entityManager->flush();
            // find first vpnSubnet in range
            $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
            $query->andWhere('vs.ipLong >= :startIp');
            $query->setParameter('startIp', $range->getRangeStartIp());
            $query->andWhere('vs.network = :network');
            $query->setParameter('network', $range->getSubnet());
            $query->andWhere('vs.type = :type');
            $query->setParameter('type', $range->getType());
            $query->addOrderBy('vs.ipLong', 'ASC');
            $query->setMaxResults(1);
            $foundSubnet = $query->getQuery()->getOneOrNullResult();

            if ($foundSubnet) {
                // Try to merge this subnet
                $this->mergeSubnet($foundSubnet);
            }

            $this->entityManager->flush();
            $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
            $query->andWhere('vs.ipLong <= :endIp');
            $query->setParameter('endIp', $range->getRangeEndIp());
            $query->andWhere('vs.network = :network');
            $query->setParameter('network', $range->getSubnet());
            $query->andWhere('vs.type = :type');
            $query->setParameter('type', $range->getType());
            $query->addOrderBy('vs.ipLong', 'DESC');
            $query->setMaxResults(1);
            $foundSubnet = $query->getQuery()->getOneOrNullResult();

            if ($foundSubnet) {
                // Try to merge this subnet
                $this->mergeSubnet($foundSubnet);
            }

            $this->entityManager->flush();
        }
    }

    private function updateVpnAddressesByRanges(array $ranges): void
    {
        // Before using this method all needed rangesToRemove should be removed
        // This will mean that subnets to be updated are contained in ranges
        foreach ($ranges as $range) {
            $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
            $query->update(VpnSubnet::class, 'vs');
            $query->set('vs.network', ':subnet');
            $query->setParameter('subnet', $range->getSubnet());
            $query->andWhere('vs.ipLong >= :startIp');
            $query->setParameter('startIp', $range->getRangeStartIp());
            $query->andWhere('vs.ipLong + vs.size -1 <= :endIp');
            $query->setParameter('endIp', $range->getRangeEndIp());
            $query->andWhere('vs.type = :type');
            $query->setParameter('type', $range->getType());
            $result = $query->getQuery()->execute();
        }

        $this->entityManager->flush();
    }

    /**
     * Method calculates changes in networks and ranges
     * Method return array with
     * [
     *    'rangesToAdd' => [], // array of SubnetRangeModel to be added to address pool
     *    'rangesToRemove' => [], // array of SubnetRangeModel to be remove from address pool
     *    'rangesToUpdate' => [], // array of SubnetRangeModel to be updated with new subnet
     * ].
     */
    public function processSubnetsChange(VpnSubnetType $vpnSubnetType, string $previousNetworks, string $previousRanges, string $networks, string $ranges): array
    {
        $rangeProps = $this->getRangeProps($vpnSubnetType, $networks, $ranges);
        $previousRangeProps = $this->getRangeProps($vpnSubnetType, $previousNetworks, $previousRanges);

        $rangesToAdd = [];
        $rangesToRemove = [];
        $rangesToUpdate = [];

        $rangeIndex = 0;
        $previousIndex = 0;
        do {
            // only new ranges left
            if ($previousIndex >= count($previousRangeProps)) {
                if ($rangeIndex < count($rangeProps)) {
                    // add
                    $rangesToAdd[] = $rangeProps[$rangeIndex];
                    ++$rangeIndex;
                }
            }
            // only previous ranges left
            if ($rangeIndex >= count($rangeProps)) {
                if ($previousIndex < count($previousRangeProps)) {
                    // remove
                    $rangesToRemove[] = $previousRangeProps[$previousIndex];
                    ++$previousIndex;
                }
            }
            if ($rangeIndex < count($rangeProps) && $previousIndex < count($previousRangeProps)) {
                $range = $rangeProps[$rangeIndex];
                $previousRange = $previousRangeProps[$previousIndex];
                // Previous range starts before new range
                if ($previousRange->getRangeStartIp() < $range->getRangeStartIp()) {
                    if ($previousRange->getRangeEndIp() < $range->getRangeStartIp()) {
                        $rangesToRemove[] = $previousRange;
                        ++$previousIndex;
                    } else {
                        $rangeToRemove = clone $previousRange;
                        $rangeToRemove->setRangeEndIp($range->getRangeStartIp() - 1);
                        $this->updateRange($rangeToRemove);
                        $rangesToRemove[] = $rangeToRemove;

                        $previousRange->setRangeStartIp($range->getRangeStartIp());
                        $this->updateRange($previousRange);
                    }
                    // New range starts before previous range
                } elseif ($previousRange->getRangeStartIp() > $range->getRangeStartIp()) {
                    if ($range->getRangeEndIp() < $previousRange->getRangeStartIp()) {
                        $rangesToAdd[] = $range;
                        ++$rangeIndex;
                    } else {
                        $rangeToAdd = clone $range;
                        $rangeToAdd->setRangeEndIp($previousRange->getRangeStartIp() - 1);
                        $this->updateRange($rangeToAdd);
                        $rangesToAdd[] = $rangeToAdd;

                        $range->setRangeStartIp($previousRange->getRangeStartIp());
                        $this->updateRange($range);
                    }
                } else {
                    // New range and previous range starts on same IP
                    if ($previousRange->getRangeEndIp() < $range->getRangeEndIp()) {
                        if ($previousRange->getSubnet() !== $range->getSubnet()) {
                            $previousRange->setSubnet($range->getSubnet());
                            $rangesToUpdate[] = $previousRange;
                        }
                        $range->setRangeStartIp($previousRange->getRangeEndIp() + 1);
                        $this->updateRange($range);
                        ++$previousIndex;
                    } elseif ($previousRange->getRangeEndIp() > $range->getRangeEndIp()) {
                        if ($previousRange->getSubnet() !== $range->getSubnet()) {
                            $rangesToUpdate[] = $range;
                        }
                        $previousRange->setRangeStartIp($range->getRangeEndIp() + 1);
                        $this->updateRange($previousRange);
                        ++$rangeIndex;
                    } else {
                        if ($previousRange->getSubnet() !== $range->getSubnet()) {
                            $rangesToUpdate[] = $range;
                        }
                        ++$rangeIndex;
                        ++$previousIndex;
                    }
                }
            }
        } while ($rangeIndex < count($rangeProps) || $previousIndex < count($previousRangeProps));

        return [
                'rangesToAdd' => $rangesToAdd, // array of SubnetRangeModel to be added to address pool
                'rangesToRemove' => $rangesToRemove, // array of SubnetRangeModel to be remove from address pool
                'rangesToUpdate' => $rangesToUpdate, // array of SubnetRangeModel to be updated with new subnet
       ];
    }

    // Method checks if all addresses in range are available in pool (none is assigned)
    public function canRangeBeRemoved(SubnetRangeModel $range): bool
    {
        $startIpLong = $range->getRangeStartIp();
        do {
            // Query is limited to 10 results to limit memory usage
            $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
            $query->andWhere('vs.ipLong <= :endIpLong');
            $query->setParameter('endIpLong', $range->getRangeEndIp());
            $query->andWhere('vs.ipLong + vs.size -1  >= :startIpLong');
            $query->setParameter('startIpLong', $startIpLong);
            $query->andWhere('vs.network = :network');
            $query->setParameter('network', $range->getSubnet());
            $query->andWhere('vs.type = :type');
            $query->setParameter('type', $range->getType());
            $query->addOrderBy('vs.ipLong', 'ASC');
            $query->setMaxResults(10);
            $results = $query->getQuery()->getResult();
            // no ip's available in pool
            if (0 == count($results)) {
                return false;
            }

            foreach ($results as $vpnSubnet) {
                // there is at least one used IP
                if ($vpnSubnet->getIpLong() > $startIpLong) {
                    return false;
                }

                // for next vpnSubnet start looking from first address after this vpnSubnet
                $startIpLong = $vpnSubnet->getIpLong() + $vpnSubnet->getSize();
            }
            // less or equal because $startIpLong represents IP that should be verified (in next pass)
            // if $startIpLong = endIp that means endIp was not verified yet
        } while ($startIpLong <= $range->getRangeEndIp());

        return true;
    }

    // Method is private due to: lack of state validation and data persisting - this is most efficient approach
    private function getSubnetByCidr(int $cidr, VpnSubnetType $vpnSubnetType): ?string
    {
        if (!$this->isCidrValid($cidr)) {
            $this->vpnLogManager->createLogCritical('log.vpn.invalidSubnetCidr', ['cidr' => $cidr]);

            return null;
        }

        $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
        $query->andWhere('vs.cidr <= :cidr');
        $query->setParameter('cidr', $cidr);
        $query->andWhere('vs.type = :type');
        $query->setParameter('type', $vpnSubnetType);
        $query->addOrderBy('vs.cidr', 'DESC');
        $query->addOrderBy('vs.ipLong', 'ASC');
        $query->setMaxResults(1);
        $foundSubnet = $query->getQuery()->getOneOrNullResult();

        if (!$foundSubnet) {
            $this->vpnLogManager->createLogCritical('log.vpn.subnetNotAvailable', ['cidr' => $cidr, 'networks' => implode(',', $networks)]);

            return null;
        }

        $resultSubnet = $this->splitSubnet($foundSubnet, $cidr);

        $this->entityManager->remove($resultSubnet);

        $this->entityManager->flush();

        return $resultSubnet->getIp().'/'.$resultSubnet->getCidr();
    }

    // Method is private due to: lack of state validation and data persisting - this is most efficient approach
    private function addVirtualIpLongSubnet(int $ipLong, int $cidr): bool
    {
        $network = $this->findNetwork($ipLong, $cidr);
        if (!$network) {
            $this->vpnLogManager->createLogCritical('log.vpn.networkNotFound', ['subnet' => long2ip($ipLong).'/'.$cidr]);

            return false;
        }

        $vpnSubnetType = $this->findVpnSubnetType($network);
        if (!$vpnSubnetType) {
            $this->vpnLogManager->createLogCritical('log.vpn.vpnSubnetTypeNotFound', ['network' => $network]);

            return false;
        }

        if (!$this->isSubnetIpLongAddressValid($ipLong, $cidr)) {
            $this->vpnLogManager->createLogCritical('log.vpn.invalidIpV4Subnet', ['subnet' => long2ip($ipLong).'/'.$cidr]);

            return false;
        }

        $devicesRanges = $this->getRangeProps(
            VpnSubnetType::DEVICE_VPN_IP,
            $this->getConfiguration()->getDevicesVpnNetworks(),
            $this->getConfiguration()->getDevicesVpnNetworksRanges()
        );

        $techniciansRanges = $this->getRangeProps(
            VpnSubnetType::TECHNICIAN_VPN_IP,
            $this->getConfiguration()->getTechniciansVpnNetworks(),
            $this->getConfiguration()->getTechniciansVpnNetworksRanges()
        );

        $virtualRanges = $this->getRangeProps(
            VpnSubnetType::DEVICE_VIRTUAL_IP,
            $this->getConfiguration()->getDevicesVirtualVpnNetworks(),
            $this->getConfiguration()->getDevicesVirtualVpnNetworksRanges()
        );

        $ranges = array_merge($devicesRanges, $techniciansRanges, $virtualRanges);

        $ipLongEnd = $ipLong + $this->cidrToSize($cidr) - 1;

        $rangeToAdd = new SubnetRangeModel();
        $rangeToAdd->setRangeStartIp($ipLong);
        $rangeToAdd->setRangeEndIp($ipLongEnd);
        $rangeToAdd->setRangeSize($ipLongEnd - $ipLong + 1);

        // calculate list of ranges that $rangeToAdd overlaps - those should be handled
        $overlappedRanges = [];
        foreach ($ranges as $range) {
            if ($this->isRangeOverlapRange($rangeToAdd, $range)) {
                $overlappedRanges[] = $range;
            }
        }

        // If no ranges are overlapped $rangeToAdd is discarded
        if (0 == count($overlappedRanges)) {
            $this->vpnLogManager->createLogWarning('log.vpn.rangeNotFound', ['subnet' => long2ip($ipLong).'/'.$cidr]);

            return false;
        }

        $rangesArrayToAdd = $this->getSubnetsToAddInRanges($rangeToAdd, $overlappedRanges);

        // find all vpn subnets in range of added
        $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
        $query->andWhere('(vs.ipLong + vs.size -1) >= :ipLong');
        $query->andWhere('vs.ipLong <= :ipLongEnd');
        $query->setParameter('ipLong', $ipLong);
        $query->setParameter('ipLongEnd', $ipLongEnd);
        $existingVpnSubnets = $query->getQuery()->getResult();

        $subnetsArrayToAdd = $this->getSubnetsToAddNotInExistingVpnSubnets($rangesArrayToAdd, $existingVpnSubnets);

        foreach ($subnetsArrayToAdd as $subnetToAdd) {
            $subnet = new VpnSubnet();
            $subnet->setIp(long2ip($subnetToAdd->getRangeStartIp()));
            $subnet->setIpLong($subnetToAdd->getRangeStartIp());
            $subnet->setCidr($this->sizeToCidr($subnetToAdd->getRangeSize()));
            $subnet->setSize($subnetToAdd->getRangeSize());
            $subnet->setNetwork($network);
            $subnet->setType($vpnSubnetType);

            $this->entityManager->persist($subnet);
            $this->entityManager->flush();

            $resultSubnet = $this->mergeSubnet($subnet);
        }

        return true;
    }

    private function getSubnetsToAddNotInExistingVpnSubnets(array $rangesArrayToHandle, array|Collection $existingVpnSubnets): array
    {
        $rangesArrayToAdd = [];

        while (count($rangesArrayToHandle) > 0) {
            $rangeToHandle = array_pop($rangesArrayToHandle);
            // check if $rangeToHandle is already completly contained by one of $existingVpnSubnets if yes - discard it
            $isRangeContained = false;
            foreach ($existingVpnSubnets as $existingVpnSubnet) {
                if ($this->isRangeContainedInVpnSubnet($rangeToHandle, $existingVpnSubnet)) {
                    $isRangeContained = true;
                    break;
                }
            }

            // if $isRangeContained - nothing else needs to be done
            if ($isRangeContained) {
                $this->vpnLogManager->createLogWarning('log.vpn.subnetInsideVpnSubnet', ['subnet' => long2ip($rangeToHandle->getRangeStartIp()).'/'.$this->sizeToCidr($rangeToHandle->getRangeSize())]);
                continue;
            }

            // check if $rangeToHandle does NOT overlap any of $existingVpnSubnet - if yes - $rangeToHandle should be added to VpnSubnet pool
            $isRangeOverlapExistingVpnSubnet = false;
            foreach ($existingVpnSubnets as $existingVpnSubnet) {
                if ($this->isRangeOverlapVpnSubnet($rangeToHandle, $existingVpnSubnet)) {
                    $isRangeOverlapExistingVpnSubnet = true;
                    break;
                }
            }

            // if !$isRangeOverlapExistingVpnSubnet - $rangeToHandle should be added to VpnSubnet pool
            if (!$isRangeOverlapExistingVpnSubnet) {
                $rangesArrayToAdd[] = $rangeToHandle;
                continue;
            }

            // if range overlaps any of $existingVpnSubnet, it need to be divided, until it will fall in one of groups above
            $rangeSize = $rangeToHandle->getRangeSize();

            // this is one address range and it should already be assigned into one of groups (toAdd or discard)
            if ($rangeSize < 2) {
                throw new \Exception('Invalid range size in VpnAddressManager::getSubnetsToAddNotInExistingVpnSubnets');
            }

            $rangeSize = $rangeSize / 2;

            $dividedRangeToHandle = new SubnetRangeModel();
            $dividedRangeToHandle->setRangeSize($rangeSize);
            $dividedRangeToHandle->setRangeStartIp($rangeToHandle->getRangeStartIp() + $rangeSize);
            $dividedRangeToHandle->setRangeEndIp($rangeToHandle->getRangeEndIp());

            $rangeToHandle->setRangeSize($rangeSize);
            $rangeToHandle->setRangeEndIp($rangeToHandle->getRangeEndIp() - $rangeSize);

            $rangesArrayToHandle[] = $rangeToHandle;
            $rangesArrayToHandle[] = $dividedRangeToHandle;
        }

        return $rangesArrayToAdd;
    }

    private function getSubnetsToAddInRanges(SubnetRangeModel $rangeToAdd, array $overlappedRanges): array
    {
        $rangesArrayToHandle = [$rangeToAdd];
        $rangesArrayToAdd = [];

        while (count($rangesArrayToHandle) > 0) {
            $rangeToHandle = array_pop($rangesArrayToHandle);
            // check if $rangeToHandle is already completly contained by one of overlapped ranges if yes - move it to $rangesArrayToAdd
            $isRangeContained = false;
            foreach ($overlappedRanges as $overlappedRange) {
                if ($this->isRangeContainedInRange($rangeToHandle, $overlappedRange)) {
                    $rangesArrayToAdd[] = $rangeToHandle;
                    $isRangeContained = true;
                    break;
                }
            }

            // if $isRangeContained - nothing else needs to be done
            if ($isRangeContained) {
                continue;
            }

            // check if $rangeToHandle overlaps any of $overlappedRange - after division it might not and should be discarded
            $isRangeInside = false;
            foreach ($overlappedRanges as $overlappedRange) {
                if ($this->isRangeOverlapRange($rangeToHandle, $overlappedRange)) {
                    $isRangeInside = true;
                    break;
                }
            }

            // if !$isRangeInside - nothing else needs to be done - range is not inside any of available ranges so it should be discarded
            if (!$isRangeInside) {
                $this->vpnLogManager->createLogWarning('log.vpn.subnetOutsideOfAvailableRange', ['subnet' => long2ip($rangeToHandle->getRangeStartIp()).'/'.$this->sizeToCidr($rangeToHandle->getRangeSize())]);
                continue;
            }

            // if range is not fully contained by ranges (but overlaps), it need to be divided, until it will fall in one of groups above
            $rangeSize = $rangeToHandle->getRangeSize();

            // this is one address range and it should already be assigned into one of groups (toAdd or discard)
            if ($rangeSize < 2) {
                throw new \Exception('Invalid range size in VpnAddressManager::getSubnetsToAddInRanges');
            }

            $rangeSize = $rangeSize / 2;

            $dividedRangeToHandle = new SubnetRangeModel();
            $dividedRangeToHandle->setRangeSize($rangeSize);
            $dividedRangeToHandle->setRangeStartIp($rangeToHandle->getRangeStartIp() + $rangeSize);
            $dividedRangeToHandle->setRangeEndIp($rangeToHandle->getRangeEndIp());

            $rangeToHandle->setRangeSize($rangeSize);
            $rangeToHandle->setRangeEndIp($rangeToHandle->getRangeEndIp() - $rangeSize);

            $rangesArrayToHandle[] = $rangeToHandle;
            $rangesArrayToHandle[] = $dividedRangeToHandle;
        }

        return $rangesArrayToAdd;
    }

    // Method is private due to: lack of state validation and data persisting - this is most efficient approach
    private function addSubnet(VpnSubnetType $vpnSubnetType, string $subnets, string $ranges): void
    {
        $subnetsArray = explode(',', $subnets);
        $rangesArray = explode(',', $ranges);
        foreach ($subnetsArray as $subnet) {
            list($ip, $cidrString) = explode('/', $subnet);
            $cidr = intval($cidrString);
            $ipLong = ip2long($ip);
            $lastHost = $ipLong + $this->cidrToSize($cidr) - 1;

            foreach ($rangesArray as $key => $range) {
                list($rangeFrom, $rangeTo) = explode('-', $range);
                $rangeFromLong = ip2long($rangeFrom);
                $rangeToLong = ip2long($rangeTo);
                if ($rangeFromLong >= $ipLong && $rangeToLong <= $lastHost) {
                    $this->addSubnetRange($vpnSubnetType, $subnet, $rangeFromLong, $rangeToLong);
                    unset($rangesArray[$key]);
                }
            }
        }
    }

    // Method is private due to: lack of state validation and data persisting - this is most efficient approach
    private function addSubnetRange(VpnSubnetType $vpnSubnetType, string $subnet, int $rangeFromLong, int $rangeToLong): void
    {
        $startIpLong = $rangeFromLong;

        do {
            $size = 1;
            while ($startIpLong + ($size * 2) - 1 <= $rangeToLong && 0 == $startIpLong % ($size * 2)) {
                $size = $size * 2;
            }

            $cidr = $this->sizeToCidr($size);

            $vpnSubnet = new VpnSubnet();
            $vpnSubnet->setIp(long2ip($startIpLong));
            $vpnSubnet->setIpLong($startIpLong);
            $vpnSubnet->setCidr($cidr);
            $vpnSubnet->setSize($this->cidrToSize($cidr));
            $vpnSubnet->setNetwork($subnet);
            $vpnSubnet->setType($vpnSubnetType);

            $this->entityManager->persist($vpnSubnet);

            $startIpLong = $startIpLong + $size;
        } while ($startIpLong <= $rangeToLong);
    }

    // Method is private due to: lack of state validation and data persisting - this is most efficient approach
    private function mergeSubnet(VpnSubnet $subnet): VpnSubnet
    {
        $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
        $query->andWhere('vs.ipLong < :ipLong');
        $query->setParameter('ipLong', $subnet->getIpLong());
        $query->addOrderBy('vs.ipLong', 'DESC');
        $query->setMaxResults(1);
        $foundSubnetLow = $query->getQuery()->getOneOrNullResult();

        if ($foundSubnetLow) {
            $subnetResult = $this->tryMergeSubnets($foundSubnetLow, $subnet);
            if ($subnetResult) {
                return $this->mergeSubnet($subnetResult);
            }
        }

        $query = $this->entityManager->getRepository(VpnSubnet::class)->createQueryBuilder('vs');
        $query->andWhere('vs.ipLong > :ipLong');
        $query->setParameter('ipLong', $subnet->getIpLong());
        $query->addOrderBy('vs.ipLong', 'ASC');
        $query->setMaxResults(1);
        $foundSubnetHigh = $query->getQuery()->getOneOrNullResult();

        if ($foundSubnetHigh) {
            $subnetResult = $this->tryMergeSubnets($subnet, $foundSubnetHigh);
            if ($subnetResult) {
                return $this->mergeSubnet($subnetResult);
            }
        }

        return $subnet;
    }

    // Method is private due to: lack of state validation and data persisting - this is most efficient approach
    private function tryMergeSubnets(VpnSubnet $subnetLow, VpnSubnet $subnetHigh): ?VpnSubnet
    {
        if ($subnetLow->getCidr() - 1 < 1) {
            return null;
        }

        if ($subnetLow->getType() != $subnetHigh->getType() || $subnetLow->getNetwork() != $subnetHigh->getNetwork()) {
            return null;
        }

        if ($subnetLow->getIpLong() + $subnetLow->getSize() == $subnetHigh->getIpLong() && $subnetLow->getCidr() == $subnetHigh->getCidr()) {
            $networkLong = $subnetLow->getIpLong() & (0xFFFFFFFF << (32 - ($subnetLow->getCidr() - 1)));

            if ($networkLong == $subnetLow->getIpLong()) {
                $subnetLow->setCidr($subnetLow->getCidr() - 1);
                $subnetLow->setSize($subnetLow->getSize() << 1); // multiply by 2

                $this->entityManager->persist($subnetLow);
                $this->entityManager->remove($subnetHigh);

                $this->entityManager->flush();

                return $subnetLow;
            }
        }

        return null;
    }

    // Method is private due to: lack of state validation and data persisting - this is most efficient approach
    private function splitSubnet(VpnSubnet $subnet, int $cidr): VpnSubnet
    {
        if ($subnet->getCidr() < $cidr) {
            if ($subnet->getCidr() + 1 > 32) {
                return $subnet;
            }

            $subnetLow = new VpnSubnet();
            $subnetLow->setIp($subnet->getIp());
            $subnetLow->setIpLong($subnet->getIpLong());
            $subnetLow->setCidr($subnet->getCidr() + 1);
            $subnetLow->setSize($subnet->getSize() >> 1); // divide by 2
            $subnetLow->setNetwork($subnet->getNetwork());
            $subnetLow->setType($subnet->getType());

            $this->entityManager->persist($subnetLow);

            $subnetHigh = new VpnSubnet();
            $subnetHigh->setNetwork($subnet->getNetwork());
            $subnetHigh->setCidr($subnet->getCidr() + 1);
            $subnetHigh->setSize($subnet->getSize() >> 1); // divide by 2
            $subnetHigh->setIpLong($subnet->getIpLong() + $subnetHigh->getSize());
            $subnetHigh->setIp(long2ip($subnetHigh->getIpLong()));
            $subnetHigh->setType($subnet->getType());

            $this->entityManager->persist($subnetHigh);
            $this->entityManager->remove($subnet);

            $this->entityManager->flush();

            return $this->splitSubnet($subnetLow, $cidr);
        } else {
            return $subnet;
        }
    }
}
