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

namespace App\Service\Trait;

use App\Entity\VpnSubnet;
use App\Enum\VpnSubnetType;
use App\Model\SubnetRangeModel;

// Trait that contains simple ipv4 subnet operations for easier reading of VpnAddressManager
trait SubnetRangeModelTrait
{
    abstract public function cidrToSize(int|string $cidr): int;

    // Method returns ordered array of SubnetRangeModel with subnets data filled
    private function getSubnetProps(VpnSubnetType $vpnSubnetType, string $subnets): array
    {
        $subnetProps = [];
        $subnetsArray = explode(',', $subnets);
        foreach ($subnetsArray as $subnet) {
            if (strlen($subnet) <= 0) {
                continue;
            }
            $subnetProps[] = $this->getSubnetModel($vpnSubnetType, $subnet);
        }

        usort($subnetProps, fn ($a, $b) => $a->getSubnetStartIp() <=> $b->getSubnetStartIp());

        return $subnetProps;
    }

    // Method returns SubnetRangeModel with subnets data
    private function getSubnetModel(VpnSubnetType $vpnSubnetType, string $subnet): SubnetRangeModel
    {
        list($ip, $cidrString) = explode('/', $subnet);
        $cidr = intval($cidrString);
        $ipLong = ip2long($ip);
        $size = $this->cidrToSize($cidr);

        $subnetModel = new SubnetRangeModel();
        $subnetModel->setSubnet($subnet);
        $subnetModel->setSubnetStartIp($ipLong);
        $subnetModel->setSubnetEndIp($ipLong + $size - 1);
        $subnetModel->setSubnetSize($size);
        $subnetModel->setSubnetCidr($cidr);
        $subnetModel->setType($vpnSubnetType);

        return $subnetModel;
    }

    // Method returns array of SubnetRangeModel with subnets and ranges data filled
    public function getRangeProps(VpnSubnetType $vpnSubnetType, string $subnets, string $ranges): array
    {
        $subnetProps = $this->getSubnetProps($vpnSubnetType, $subnets);
        $rangesArray = explode(',', $ranges);
        $rangeSubnetProps = [];
        foreach ($rangesArray as $range) {
            if (strlen($range) <= 0) {
                continue;
            }
            $rangeProp = $this->getRangeModel($range);
            foreach ($subnetProps as $subnet) {
                if ($this->isRangeContainedInSubnet($rangeProp, $subnet)) {
                    $this->copySubnetProps($rangeProp, $subnet);
                    $rangeSubnetProps[] = $rangeProp;
                    break;
                }
            }
        }

        usort($rangeSubnetProps, fn ($a, $b) => $a->getRangeStartIp() <=> $b->getRangeStartIp());

        return $rangeSubnetProps;
    }

    // Method returns SubnetRangeModel with range data
    private function getRangeModel(string $range): SubnetRangeModel
    {
        list($startIp, $endIp) = explode('-', $range);
        $startIpLong = ip2long($startIp);
        $endIpLong = ip2long($endIp);
        $size = $endIpLong - $startIpLong + 1;

        $rangeModel = new SubnetRangeModel();
        $rangeModel->setRange($range);
        $rangeModel->setRangeStartIp($startIpLong);
        $rangeModel->setRangeEndIp($endIpLong);
        $rangeModel->setRangeSize($size);

        return $rangeModel;
    }

    // Method checks if whole range is contained in subnet
    private function isRangeContainedInSubnet(SubnetRangeModel $range, SubnetRangeModel $subnet): bool
    {
        if ($range->getRangeStartIp() >= $subnet->getSubnetStartIp() && $range->getRangeEndIp() <= $subnet->getSubnetEndIp()) {
            return true;
        }

        return false;
    }

    private function isRangeContainedInRange(SubnetRangeModel $containedRange, SubnetRangeModel $range): bool
    {
        if ($containedRange->getRangeStartIp() >= $range->getRangeStartIp() && $containedRange->getRangeEndIp() <= $range->getRangeEndIp()) {
            return true;
        }

        return false;
    }

    private function isRangeOverlapRange(SubnetRangeModel $range1, SubnetRangeModel $range2): bool
    {
        if ($range1->getRangeStartIp() > $range2->getRangeEndIp() || $range1->getRangeEndIp() < $range2->getRangeStartIp()) {
            return false;
        }

        return true;
    }

    // Method checks if range and vpnSubnet overlap (if any Ip's are shared)
    private function isRangeOverlapVpnSubnet(SubnetRangeModel $range, VpnSubnet $vpnSubnet): bool
    {
        if ($range->getRangeStartIp() > $vpnSubnet->getIpLong() + $vpnSubnet->getSize() - 1 || $range->getRangeEndIp() < $vpnSubnet->getIpLong()) {
            return false;
        }

        return true;
    }

    // Method checks if whole vpnSubnets is contained in range
    private function isVpnSubnetContainedInRange(SubnetRangeModel $range, VpnSubnet $vpnSubnet): bool
    {
        if ($range->getRangeStartIp() <= $vpnSubnet->getIpLong() && $range->getRangeEndIp() >= $vpnSubnet->getIpLong() + $vpnSubnet->getSize() - 1) {
            return true;
        }

        return false;
    }

    // Method checks if whole range is contained in vpnSubnet
    private function isRangeContainedInVpnSubnet(SubnetRangeModel $range, VpnSubnet $vpnSubnet): bool
    {
        if ($vpnSubnet->getIpLong() <= $range->getRangeStartIp() && $vpnSubnet->getIpLong() + $vpnSubnet->getSize() - 1 >= $range->getRangeEndIp()) {
            return true;
        }

        return false;
    }

    // Method checks if whole range is contained in subnet
    private function copySubnetProps(SubnetRangeModel $target, SubnetRangeModel $source): void
    {
        $target->setSubnet($source->getSubnet());
        $target->setSubnetStartIp($source->getSubnetStartIp());
        $target->setSubnetEndIp($source->getSubnetEndIp());
        $target->setSubnetSize($source->getSubnetSize());
        $target->setSubnetCidr($source->getSubnetCidr());
        $target->setType($source->getType());
    }

    // Method updates size and range values based on rangeStartIp and rangeEndIp
    private function updateRange(SubnetRangeModel $target): void
    {
        if (!$target->getRangeStartIp() || !$target->getRangeEndIp()) {
            return;
        }
        $target->setRangeSize($target->getRangeEndIp() - $target->getRangeStartIp() + 1);
        $target->setRange(long2ip($target->getRangeStartIp()).'-'.long2ip($target->getRangeEndIp()));
    }
}
