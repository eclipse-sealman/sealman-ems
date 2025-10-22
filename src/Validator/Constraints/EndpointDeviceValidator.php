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

namespace App\Validator\Constraints;

use App\Entity\DeviceEndpointDevice;
use App\Entity\TemplateVersionEndpointDevice;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\VpnAddressManagerTrait;
use App\Validator\Constraints\Trait\AccessTagsValidatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EndpointDeviceValidator extends ConstraintValidator
{
    use VpnAddressManagerTrait;
    use AccessTagsValidatorTrait;
    use SecurityHelperTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        if ($protocol instanceof TemplateVersionEndpointDevice) {
            $origin = $protocol->getTemplateVersion();
        } elseif ($protocol instanceof DeviceEndpointDevice) {
            $origin = $protocol->getDevice();
        } else {
            // Cannot use Symfony\Component\Validator\Exception\UnexpectedValueException due to two accepted types
            throw new \Exception('EndpointDeviceValidator only supports '.TemplateVersionEndpointDevice::class.' and '.DeviceEndpointDevice::class);
        }

        if (!$origin) {
            return;
        }

        $virtualIpHostPart = $protocol->getVirtualIpHostPart();
        $virtualSubnetCidr = $origin->getVirtualSubnetCidr();
        if (($virtualIpHostPart || 0 === $virtualIpHostPart) && $virtualSubnetCidr) {
            $subnetSize = $this->vpnAddressManager->cidrToSize($virtualSubnetCidr);
            if ($virtualIpHostPart < 0 || $virtualIpHostPart >= $subnetSize) {
                $this->context->buildViolation($constraint->messageInvalidVirtualIpHostPart)->setParameter('subnetSize', strval($subnetSize - 1))->atPath('virtualIpHostPart')->addViolation();
            }

            if (0 === $virtualIpHostPart) {
                $this->context->buildViolation($constraint->messageVirtualIpHostPartUsedByDevice)->atPath('virtualIpHostPart')->addViolation();
            }
        }

        $nameCount = 0;
        $physicalIpCount = 0;
        $virtualIpHostPartCount = 0;

        foreach ($origin->getEndpointDevices() as $endpointDevice) {
            if ($endpointDevice->getName() == $protocol->getName()) {
                ++$nameCount;
            }

            if ($endpointDevice->getPhysicalIp() == $protocol->getPhysicalIp()) {
                ++$physicalIpCount;
            }

            if ($endpointDevice->getVirtualIpHostPart() == $protocol->getVirtualIpHostPart()) {
                ++$virtualIpHostPartCount;
            }
        }

        if ($nameCount > 1) {
            $this->context->buildViolation($constraint->messageNameNotUnique)->atPath('name')->addViolation();
        }

        if ($physicalIpCount > 1) {
            $this->context->buildViolation($constraint->messagePhysicalIpNotUnique)->atPath('physicalIp')->addViolation();
        }

        if ($virtualIpHostPartCount > 1) {
            $this->context->buildViolation($constraint->messageVirtualIpHostPartNotUnique)->atPath('virtualIpHostPart')->addViolation();
        }

        // Only ROLE_VPN_ENDPOINTDEVICES role has access tags limited, because it's only role that can use it in form (except of ROLE_ADMIN)
        if ($this->isGranted('ROLE_VPN_ENDPOINTDEVICES')) {
            // Skip injected endpoint devices access tag validation as their values are copied by our code and we trust them
            if (!$protocol->getDevice()->getInjectedEndpointDevices()->contains($protocol)) {
                // Overridden endpoint devices should yield access denied always
                if ($protocol->getDevice()->getOverriddenEndpointDevices()->contains($protocol)) {
                    $this->context->buildViolation($constraint->messageAccessDenied)->atPath('name')->addViolation();
                } else {
                    $this->validateAccessTags($protocol);
                }
            }
        }
    }
}
