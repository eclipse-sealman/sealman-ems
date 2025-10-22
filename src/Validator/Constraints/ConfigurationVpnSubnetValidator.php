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

use App\Entity\Configuration;
use App\Enum\VpnSubnetType;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\VpnAddressManagerTrait;
use Carve\ApiBundle\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConfigurationVpnSubnetValidator extends ConstraintValidator
{
    use VpnAddressManagerTrait;
    use EntityManagerTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        if (!$this->basicSubnetValidation($protocol, $constraint)) {
            // basic validation has failed, no sense to do more validation before this errors are fixed
            return;
        }

        if (!$this->basicRangeValidation($protocol, $constraint)) {
            // basic validation has failed, no sense to do more validation before this errors are fixed
            return;
        }

        if (!$this->overlapSubnetValidation($protocol, $constraint)) {
            // overlap validation has failed, no sense to do more validation before this errors are fixed
            return;
        }

        if (!$this->subnetContainRangeValidation($protocol, $constraint)) {
            // subnet contain range validation has failed, no sense to do more validation before this errors are fixed
            return;
        }

        if (!$this->overlapRangeValidation($protocol, $constraint)) {
            // overlap validation has failed, no sense to do more validation before this errors are fixed
            return;
        }

        // check if ranges to be removed can be removed

        if (!$this->rangesToBeRemovedValidation($protocol, $constraint)) {
            // overlap validation has failed, no sense to do more validation before this errors are fixed
            return;
        }
    }

    // Basic subnets validation - check if NotBlank, if subnets are valid IPv4 subnets e.g. 10.0.0.1/8 is not valid
    protected function basicSubnetValidation(Configuration $protocol, Constraint $constraint): bool
    {
        $basicFieldValidated = true;
        if ($this->processValidation($protocol->getDevicesVpnNetworks(), new Subnet(), 'devicesVpnNetworks')) {
            $basicFieldValidated = false;
        }
        if ($this->processValidation($protocol->getTechniciansVpnNetworks(), new Subnet(), 'techniciansVpnNetworks')) {
            $basicFieldValidated = false;
        }

        if ($this->processValidation($protocol->getDevicesVirtualVpnNetworks(), new NotBlank(), 'devicesVirtualVpnNetworks')) {
            $basicFieldValidated = false;
        } else {
            // If devices virtual networks in not blank each value in list should be validated
            $subnetList = explode(',', $protocol->getDevicesVirtualVpnNetworks());
            if (count($subnetList) <= 0) {
                // no elements
                $this->context->buildViolation($constraint->messageInvalidList)->atPath('devicesVirtualVpnNetworks')->addViolation();
                $basicFieldValidated = false;
            } else {
                foreach ($subnetList as $subnet) {
                    if ($this->processValidation($subnet, new Subnet(), 'devicesVirtualVpnNetworks')) {
                        $basicFieldValidated = false;
                    }
                }
            }
        }

        return $basicFieldValidated;
    }

    // Basic ranges validation - check if NotBlank, if ranges are valid IPv4 ranges e.g. 10.0.0.1-10.0.0.10
    protected function basicRangeValidation(Configuration $protocol, Constraint $constraint): bool
    {
        $basicFieldValidated = true;
        if (!$this->basicRangeFieldValidation($protocol->getDevicesVpnNetworksRanges(), 'devicesVpnNetworksRanges', $constraint)) {
            $basicFieldValidated = false;
        }

        if (!$this->basicRangeFieldValidation($protocol->getTechniciansVpnNetworksRanges(), 'techniciansVpnNetworksRanges', $constraint)) {
            $basicFieldValidated = false;
        }

        if (!$this->basicRangeFieldValidation($protocol->getDevicesVirtualVpnNetworksRanges(), 'devicesVirtualVpnNetworksRanges', $constraint)) {
            $basicFieldValidated = false;
        }

        return $basicFieldValidated;
    }

    // Basic ranges validation - for one specific ranges field only - check if NotBlank, if ranges are valid IPv4 ranges e.g. 10.0.0.1-10.0.0.10
    protected function basicRangeFieldValidation(?string $fieldValue, string $fieldName, Constraint $constraint): bool
    {
        if ($this->processValidation($fieldValue, new NotBlank(), $fieldName)) {
            return false;
        } else {
            // If devices virtual networks in not blank each value in list should be validated
            $subnetList = explode(',', $fieldValue);
            if (count($subnetList) <= 0) {
                // no elements
                $this->context->buildViolation($constraint->messageInvalidList)->atPath($fieldName)->addViolation();

                return false;
            } else {
                foreach ($subnetList as $subnet) {
                    if ($this->processValidation($subnet, new IpRange(), $fieldName)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    // Overlap subnets validation - check if all provided subnets are not overlapping each other
    protected function overlapSubnetValidation(Configuration $protocol, Constraint $constraint): bool
    {
        $overlapValidated = true;

        if ($this->vpnAddressManager->isSubnetOverlap($protocol->getDevicesVpnNetworks(), $protocol->getTechniciansVpnNetworks())) {
            $this->context->buildViolation($constraint->messageInvalidOverlap, ['{{ subnet1 }}' => $protocol->getDevicesVpnNetworks(), '{{ subnet2 }}' => $protocol->getTechniciansVpnNetworks()])->atPath('devicesVpnNetworks')->addViolation();
            $this->context->buildViolation($constraint->messageInvalidOverlap, ['{{ subnet1 }}' => $protocol->getDevicesVpnNetworks(), '{{ subnet2 }}' => $protocol->getTechniciansVpnNetworks()])->atPath('techniciansVpnNetworks')->addViolation();
            $overlapValidated = false;
        }

        $subnetList = explode(',', $protocol->getDevicesVirtualVpnNetworks());
        foreach ($subnetList as $key1 => $firstSubnet) {
            foreach ($subnetList as $key2 => $secondSubnet) {
                if ($key1 != $key2) {
                    if ($this->vpnAddressManager->isSubnetOverlap($firstSubnet, $secondSubnet)) {
                        $this->context->buildViolation($constraint->messageInvalidOverlap, ['{{ subnet1 }}' => $firstSubnet, '{{ subnet2 }}' => $secondSubnet])->atPath('devicesVirtualVpnNetworks')->addViolation();
                        $overlapValidated = false;
                    }
                }
            }
        }

        $firstSubnet = $protocol->getDevicesVpnNetworks();
        foreach ($subnetList as $secondSubnet) {
            if ($this->vpnAddressManager->isSubnetOverlap($firstSubnet, $secondSubnet)) {
                $this->context->buildViolation($constraint->messageInvalidOverlap, ['{{ subnet1 }}' => $firstSubnet, '{{ subnet2 }}' => $secondSubnet])->atPath('devicesVirtualVpnNetworks')->addViolation();
                $this->context->buildViolation($constraint->messageInvalidOverlap, ['{{ subnet1 }}' => $firstSubnet, '{{ subnet2 }}' => $secondSubnet])->atPath('devicesVpnNetworks')->addViolation();
                $overlapValidated = false;
            }
        }

        $firstSubnet = $protocol->getTechniciansVpnNetworks();
        foreach ($subnetList as $secondSubnet) {
            if ($this->vpnAddressManager->isSubnetOverlap($firstSubnet, $secondSubnet)) {
                $this->context->buildViolation($constraint->messageInvalidOverlap, ['{{ subnet1 }}' => $firstSubnet, '{{ subnet2 }}' => $secondSubnet])->atPath('devicesVirtualVpnNetworks')->addViolation();
                $this->context->buildViolation($constraint->messageInvalidOverlap, ['{{ subnet1 }}' => $firstSubnet, '{{ subnet2 }}' => $secondSubnet])->atPath('techniciansVpnNetworks')->addViolation();
                $overlapValidated = false;
            }
        }

        return $overlapValidated;
    }

    // Overlap ranges validation - check if all provided ranges are not overlapping each other
    protected function overlapRangeValidation(Configuration $protocol, Constraint $constraint): bool
    {
        $isValid = true;

        // returned ranges are already sorted
        $devicesRanges = $this->vpnAddressManager->getRangeProps(
            VpnSubnetType::DEVICE_VPN_IP,
            $protocol->getDevicesVpnNetworks(),
            $protocol->getDevicesVpnNetworksRanges()
        );
        $techniciansRanges = $this->vpnAddressManager->getRangeProps(
            VpnSubnetType::TECHNICIAN_VPN_IP,
            $protocol->getTechniciansVpnNetworks(),
            $protocol->getTechniciansVpnNetworksRanges()
        );
        $virtualRanges = $this->vpnAddressManager->getRangeProps(
            VpnSubnetType::DEVICE_VIRTUAL_IP,
            $protocol->getDevicesVirtualVpnNetworks(),
            $protocol->getDevicesVirtualVpnNetworksRanges()
        );

        if (!$this->overlapSortedSubnetRangeModelValidation($devicesRanges, ['devicesVpnNetworksRanges'], $constraint)) {
            $isValid = false;
        }
        if (!$this->overlapSortedSubnetRangeModelValidation($techniciansRanges, ['techniciansVpnNetworksRanges'], $constraint)) {
            $isValid = false;
        }
        if (!$this->overlapSortedSubnetRangeModelValidation($virtualRanges, ['devicesVirtualVpnNetworksRanges'], $constraint)) {
            $isValid = false;
        }

        // previous part has to be valid to make sure correct validation errors will be generated while doing pair tests
        if (!$isValid) {
            return false;
        }

        $devicesTechniciansRanges = array_merge($devicesRanges, $techniciansRanges);
        usort($devicesTechniciansRanges, fn ($a, $b) => $a->getRangeStartIp() <=> $b->getRangeStartIp());

        if (!$this->overlapSortedSubnetRangeModelValidation($devicesTechniciansRanges, ['devicesVpnNetworksRanges', 'techniciansVpnNetworksRanges'], $constraint)) {
            $isValid = false;
        }

        $devicesVirtualRanges = array_merge($devicesRanges, $virtualRanges);
        usort($devicesVirtualRanges, fn ($a, $b) => $a->getRangeStartIp() <=> $b->getRangeStartIp());

        if (!$this->overlapSortedSubnetRangeModelValidation($devicesVirtualRanges, ['devicesVpnNetworksRanges', 'devicesVirtualVpnNetworksRanges'], $constraint)) {
            $isValid = false;
        }

        $techniciansVirtualRanges = array_merge($techniciansRanges, $virtualRanges);
        usort($techniciansVirtualRanges, fn ($a, $b) => $a->getRangeStartIp() <=> $b->getRangeStartIp());
        if (!$this->overlapSortedSubnetRangeModelValidation($techniciansVirtualRanges, ['techniciansVpnNetworksRanges', 'devicesVirtualVpnNetworksRanges'], $constraint)) {
            $isValid = false;
        }

        return $isValid;
    }

    protected function overlapSortedSubnetRangeModelValidation(array $sortedRanges, array $fieldNames, Constraint $constraint): bool
    {
        // if one or less it cannot overlap
        if (count($sortedRanges) < 2) {
            return true;
        }

        $previousRange = null;
        foreach ($sortedRanges as $range) {
            if (!$previousRange) {
                $previousRange = $range;
                continue;
            }

            if ($previousRange->getRangeEndIp() >= $range->getRangeStartIp()) {
                foreach ($fieldNames as $fieldName) {
                    $this->context->buildViolation(
                        $constraint->messageInvalidRangesOverlap,
                        ['{{ range1 }}' => $previousRange->getRange(), '{{ range2 }}' => $range->getRange()]
                    )->atPath($fieldName)->addViolation();
                }

                return false;
            }

            $previousRange = $range;
        }

        return true;
    }

    // Subnet contain range validation - check if all provided ranges are contained in provided subnets
    protected function subnetContainRangeValidation(Configuration $protocol, Constraint $constraint): bool
    {
        $isValid = true;

        // method getRangeProps will not return ranges not contained by subnet

        $devicesRangesArray = explode(',', $protocol->getDevicesVpnNetworksRanges());
        $devicesRanges = $this->vpnAddressManager->getRangeProps(
            VpnSubnetType::DEVICE_VPN_IP,
            $protocol->getDevicesVpnNetworks(),
            $protocol->getDevicesVpnNetworksRanges()
        );

        if (count($devicesRanges) != count($devicesRangesArray)) {
            $this->context->buildViolation($constraint->messageInvalidRangeOutsideSubnet)->atPath('devicesVpnNetworksRanges')->addViolation();

            $isValid = false;
        }

        $techniciansRangesArray = explode(',', $protocol->getTechniciansVpnNetworksRanges());
        $techniciansRanges = $this->vpnAddressManager->getRangeProps(
            VpnSubnetType::TECHNICIAN_VPN_IP,
            $protocol->getTechniciansVpnNetworks(),
            $protocol->getTechniciansVpnNetworksRanges()
        );

        if (count($techniciansRanges) != count($techniciansRangesArray)) {
            $this->context->buildViolation($constraint->messageInvalidRangeOutsideSubnet)->atPath('techniciansVpnNetworksRanges')->addViolation();

            $isValid = false;
        }

        $virtualRangesArray = explode(',', $protocol->getDevicesVirtualVpnNetworksRanges());
        $virtualRanges = $this->vpnAddressManager->getRangeProps(
            VpnSubnetType::DEVICE_VIRTUAL_IP,
            $protocol->getDevicesVirtualVpnNetworks(),
            $protocol->getDevicesVirtualVpnNetworksRanges()
        );

        if (count($virtualRanges) != count($virtualRangesArray)) {
            $this->context->buildViolation($constraint->messageInvalidRangeOutsideSubnet)->atPath('devicesVirtualVpnNetworksRanges')->addViolation();

            $isValid = false;
        }

        return $isValid;
    }

    // Check if ranges that should be removed after this configuration change are fully unused (none of IP's is assigned)
    protected function rangesToBeRemovedValidation(Configuration $protocol, Constraint $constraint): bool
    {
        $previousConfiguration = $this->entityManager->getUnitOfWork()->getOriginalEntityData($protocol);

        $basicFieldValidated = true;
        if (
            !$this->rangesToBeRemovedFieldValidation(
                VpnSubnetType::DEVICE_VPN_IP,
                $previousConfiguration['devicesVpnNetworks'],
                $previousConfiguration['devicesVpnNetworksRanges'],
                $protocol->getDevicesVpnNetworks(),
                $protocol->getDevicesVpnNetworksRanges(),
                'devicesVpnNetworksRanges',
                $constraint
                )
            ) {
            $basicFieldValidated = false;
        }

        if (
            !$this->rangesToBeRemovedFieldValidation(
                VpnSubnetType::TECHNICIAN_VPN_IP,
                $previousConfiguration['techniciansVpnNetworks'],
                $previousConfiguration['techniciansVpnNetworksRanges'],
                $protocol->getTechniciansVpnNetworks(),
                $protocol->getTechniciansVpnNetworksRanges(),
                'techniciansVpnNetworksRanges',
                $constraint
                )
            ) {
            $basicFieldValidated = false;
        }

        if (
            !$this->rangesToBeRemovedFieldValidation(
                VpnSubnetType::DEVICE_VIRTUAL_IP,
                $previousConfiguration['devicesVirtualVpnNetworks'],
                $previousConfiguration['devicesVirtualVpnNetworksRanges'],
                $protocol->getDevicesVirtualVpnNetworks(),
                $protocol->getDevicesVirtualVpnNetworksRanges(),
                'devicesVirtualVpnNetworksRanges',
                $constraint
                )
            ) {
            $basicFieldValidated = false;
        }

        return $basicFieldValidated;
    }

    // Check if ranges have changes and if ranges to vbe removed are not used
    protected function rangesToBeRemovedFieldValidation(VpnSubnetType $vpnSubnetType, string $previousNetworks, string $previousRanges, string $networks, string $ranges, string $fieldName, Constraint $constraint): bool
    {
        // no change
        if ($previousNetworks == $previousRanges && $networks == $ranges) {
            return true;
        }

        // find ranges to be removed
        $ranges = $this->vpnAddressManager->processSubnetsChange($vpnSubnetType, $previousNetworks, $previousRanges, $networks, $ranges);

        foreach ($ranges['rangesToRemove'] as $range) {
            if (!$this->vpnAddressManager->canRangeBeRemoved($range)) {
                $this->context->buildViolation($constraint->messageInvalidRangeCannotBeRemoved, ['{{ range }}' => $range->getRange()])->atPath($fieldName)->addViolation();

                return false;
            }
        }

        return true;
    }

    protected function processValidation($value, $constraints, $fieldName): bool
    {
        $validator = $this->context->getValidator();

        $validations = $validator->validate($value, $constraints);
        if ($validations->count() > 0) {
            foreach ($validations as $validation) {
                $this->context->buildViolation($validation->getMessage(), $validation->getParameters())->atPath($fieldName)->addViolation();
            }

            return true;
        }

        return false;
    }
}
