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

use Carve\ApiBundle\Exception\RequestExecutionException;

// Trait that contains simple ipv4 operations for easier reading of VpnAddressManager
trait Ipv4HelperTrait
{
    /**
     * Returns subnet size based on cidr e.g. cidr = /24 => size = 256
     * If cidr is invalid RequestExecutionException is generated.
     */
    public function cidrToSize(int|string $cidr): int
    {
        if (is_string($cidr)) {
            // This should not happen due to validation executed before
            if (is_numeric($cidr)) {
                $cidr = intval($cidr);
            } else {
                throw new RequestExecutionException('validation.ipv4.invalidCidr', ['cidr' => $cidr]);
            }
        }
        // This should not happen due to validation executed before
        if (!$this->isCidrValid($cidr)) {
            throw new RequestExecutionException('validation.ipv4.invalidCidr', ['cidr' => $cidr]);
        }

        return $this->cidrToSizeRawCalculation($cidr);
    }

    public function isCidrValid(int $cidr): bool
    {
        if ($cidr > 32) {
            return false;
        }

        if ($cidr < 0) {
            return false;
        }

        return true;
    }

    /**
     * Returns subnet cidr based on size e.g.  size = 256 => cidr = 24 (as in /24)
     * If cidr is invalid RequestExecutionException is generated.
     */
    public function sizeToCidr(int $size): int
    {
        // This should not happen due to validation executed before
        if (!$this->isSizeValid($size)) {
            throw new RequestExecutionException('validation.ipv4.invalidSubnetSize', ['size' => $size]);
        }

        return $this->sizeToCidrRawCalculation($size);
    }

    public function isSizeValid(int $size): bool
    {
        $cidr = $this->sizeToCidrRawCalculation($size);

        if (!$this->isCidrValid($cidr)) {
            return false;
        }

        return $this->cidrToSizeRawCalculation($cidr) == $size;
    }

    /**
     * Returns subnet cidr based on size e.g.  size = 256 => cidr = 24 (as in /24)
     * Just calculation - no checks - for internal use only.
     */
    private function sizeToCidrRawCalculation(int $size): int
    {
        return 32 - intval(log($size, 2));
    }

    /**
     * Returns subnet size based on cidr e.g. cidr = /24 => size = 256
     * Just calculation - no checks - for internal use only.
     */
    private function cidrToSizeRawCalculation(int $cidr): int
    {
        return pow(2, 32 - $cidr);
    }

    // canonical subnet e.g. 10.0.0.0/8 - returns ip (10.0.0.2) or null
    public function getAddressInSubnet(int $hostPart, string $subnet): ?string
    {
        if (!$this->isSubnetAddressValid($subnet)) {
            return null;
        }

        list($startIp, $cidr) = explode('/', $subnet);
        if ($hostPart < 0 || $hostPart >= $this->cidrToSize(intval($cidr))) {
            return null;
        }

        $virtualIPAddress = ip2long($startIp) + $hostPart;

        return long2ip($virtualIPAddress);
    }

    public function isSubnetAddressValid(string $subnet): bool
    {
        list($startIp, $cidrString) = explode('/', $subnet);
        $cidr = intval($cidrString);

        $ipLong = ip2long($startIp);

        return $this->isSubnetIpLongAddressValid($ipLong, $cidr);
    }

    public function isSubnetIpLongAddressValid(int $ipLong, int $cidr): bool
    {
        if (!$this->isCidrValid($cidr)) {
            return false;
        }

        if (0 != $ipLong % $this->cidrToSize($cidr)) {
            return false;
        }

        return true;
    }

    public function isSubnetOverlap(string $subnet1, string $subnet2): bool
    {
        list($ip1, $cidrString1) = explode('/', $subnet1);
        $cidr1 = intval($cidrString1);
        $ipLongStart1 = ip2long($ip1);
        $ipLongEnd1 = $ipLongStart1 + $this->cidrToSize($cidr1) - 1;

        list($ip2, $cidrString2) = explode('/', $subnet2);
        $cidr2 = intval($cidrString2);
        $ipLongStart2 = ip2long($ip2);
        $ipLongEnd2 = $ipLongStart2 + $this->cidrToSize($cidr2) - 1;

        if ($ipLongStart1 > $ipLongEnd2 || $ipLongStart2 > $ipLongEnd1) {
            return false;
        }

        return true;
    }
}
