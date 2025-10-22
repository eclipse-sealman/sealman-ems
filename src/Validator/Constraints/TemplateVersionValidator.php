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

use App\Entity\DeviceType;
use App\Enum\Feature;
use App\Enum\MasqueradeType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\UserTrait;
use App\Validator\Constraints\Trait\AccessTagsValidatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TemplateVersionValidator extends ConstraintValidator
{
    use AuthorizationCheckerTrait;
    use SecurityHelperTrait;
    use UserTrait;
    use AccessTagsValidatorTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        $deviceType = $protocol->getDeviceType();
        if (!$deviceType) {
            return;
        }

        $this->validateVariables($deviceType, $protocol, $constraint);
        $this->validateMasquerade($deviceType, $protocol, $constraint);
        $this->validateConfigs($deviceType, $protocol, $constraint);
        $this->validateFirmwares($deviceType, $protocol, $constraint);
        $this->validateEndpointDevices($deviceType, $protocol, $constraint);

        // Only ROLE_SMARTEMS role has access tags limited, because it's only role that can use it in form (except of ROLE_ADMIN)
        if ($this->isGranted('ROLE_SMARTEMS')) {
            $this->validateAccessTags($protocol);
        }
    }

    protected function validateVariables(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getHasVariables() && count($protocol->getVariables()) > 0) {
            $this->context->buildViolation($constraint->messageVariablesDisabled)->atPath('variables')->addViolation();
        }
    }

    protected function validateMasquerade(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getIsMasqueradeAvailable()) {
            if ($protocol->getMasqueradeType()) {
                $this->context->buildViolation($constraint->messageMasqueradesDisabled)->atPath('masqueradeType')->addViolation();
            }

            if (count($protocol->getMasquerades()) > 0) {
                $this->context->buildViolation($constraint->messageMasqueradesDisabled)->atPath('masquerades')->addViolation();
            }

            return;
        }

        switch ($protocol->getMasqueradeType()) {
            case MasqueradeType::ADVANCED:
                if (0 === count($protocol->getMasquerades())) {
                    $this->context->buildViolation($constraint->messageMasqueradeTypeAdvancedMasquaradesAtLeastOne)->atPath('masquerades')->addViolation();
                }
                break;
            case MasqueradeType::DEFAULT:
                if (count($protocol->getMasquerades()) > 0) {
                    $this->context->buildViolation($constraint->messageMasqueradeTypeDefaultMasquaradesMustBeEmpty)->atPath('masquerades')->addViolation();
                }
                break;
            case MasqueradeType::DISABLED:
                if (count($protocol->getMasquerades()) > 0) {
                    $this->context->buildViolation($constraint->messageMasqueradeTypeDisabledMasquaradesMustBeEmpty)->atPath('masquerades')->addViolation();
                }
                break;
            default:
                break;
        }
    }

    protected function validateConfigs(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        // Decided not to check hasAlwaysReinstallConfig flag for easier UX during API call
        // Cleanup code added to controller
        if (!$deviceType->getHasConfig1()) {
            if ($protocol->getConfig1()) {
                $this->context->buildViolation($constraint->messageConfig1Disabled)->atPath('config1')->addViolation();
            }

            if ($protocol->getReinstallConfig1()) {
                $this->context->buildViolation($constraint->messageConfig1Disabled)->atPath('reinstallConfig1')->addViolation();
            }
        } elseif ($protocol->getConfig1()) {
            if (Feature::PRIMARY !== $protocol->getConfig1()->getFeature()) {
                $this->context->buildViolation($constraint->messageConfigInvalidFeature)->atPath('config1')->addViolation();
            }

            if ($protocol->getConfig1()->getDeviceType() !== $deviceType) {
                $this->context->buildViolation($constraint->messageConfigInvalidDeviceType)->atPath('config1')->addViolation();
            }

            if (!$this->isConfigAccessible($protocol->getConfig1())) {
                $this->context->buildViolation($constraint->messageConfigInvalid)->atPath('config1')->addViolation();
            }
        }

        if (!$deviceType->getHasConfig2()) {
            if ($protocol->getConfig2()) {
                $this->context->buildViolation($constraint->messageConfig2Disabled)->atPath('config2')->addViolation();
            }

            if ($protocol->getReinstallConfig2()) {
                $this->context->buildViolation($constraint->messageConfig2Disabled)->atPath('reinstallConfig2')->addViolation();
            }
        } elseif ($protocol->getConfig2()) {
            if (Feature::SECONDARY !== $protocol->getConfig2()->getFeature()) {
                $this->context->buildViolation($constraint->messageConfigInvalidFeature)->atPath('config2')->addViolation();
            }

            if ($protocol->getConfig2()->getDeviceType() !== $deviceType) {
                $this->context->buildViolation($constraint->messageConfigInvalidDeviceType)->atPath('config2')->addViolation();
            }

            if (!$this->isConfigAccessible($protocol->getConfig2())) {
                $this->context->buildViolation($constraint->messageConfigInvalid)->atPath('config2')->addViolation();
            }
        }

        if (!$deviceType->getHasConfig3()) {
            if ($protocol->getConfig3()) {
                $this->context->buildViolation($constraint->messageConfig3Disabled)->atPath('config3')->addViolation();
            }

            if ($protocol->getReinstallConfig3()) {
                $this->context->buildViolation($constraint->messageConfig3Disabled)->atPath('reinstallConfig3')->addViolation();
            }
        } elseif ($protocol->getConfig3()) {
            if (Feature::TERTIARY !== $protocol->getConfig3()->getFeature()) {
                $this->context->buildViolation($constraint->messageConfigInvalidFeature)->atPath('config3')->addViolation();
            }

            if ($protocol->getConfig3()->getDeviceType() !== $deviceType) {
                $this->context->buildViolation($constraint->messageConfigInvalidDeviceType)->atPath('config3')->addViolation();
            }

            if (!$this->isConfigAccessible($protocol->getConfig3())) {
                $this->context->buildViolation($constraint->messageConfigInvalid)->atPath('config3')->addViolation();
            }
        }
    }

    protected function validateFirmwares(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getHasFirmware1()) {
            if ($protocol->getFirmware1()) {
                $this->context->buildViolation($constraint->messageFirmware1Disabled)->atPath('firmware1')->addViolation();
            }

            if ($protocol->getReinstallFirmware1()) {
                $this->context->buildViolation($constraint->messageFirmware1Disabled)->atPath('reinstallFirmware1')->addViolation();
            }
        } elseif ($protocol->getFirmware1()) {
            if (Feature::PRIMARY !== $protocol->getFirmware1()->getFeature()) {
                $this->context->buildViolation($constraint->messageFirmwareInvalidFeature)->atPath('firmware1')->addViolation();
            }

            if ($protocol->getFirmware1()->getDeviceType() !== $deviceType) {
                $this->context->buildViolation($constraint->messageFirmwareInvalidDeviceType)->atPath('firmware1')->addViolation();
            }

            if (!$this->isFirmwareAccessible($protocol->getFirmware1())) {
                $this->context->buildViolation($constraint->messageFirmwareInvalid)->atPath('firmware1')->addViolation();
            }
        }

        if (!$deviceType->getHasFirmware2()) {
            if ($protocol->getFirmware2()) {
                $this->context->buildViolation($constraint->messageFirmware2Disabled)->atPath('firmware2')->addViolation();
            }

            if ($protocol->getReinstallFirmware2()) {
                $this->context->buildViolation($constraint->messageFirmware2Disabled)->atPath('reinstallFirmware2')->addViolation();
            }
        } elseif ($protocol->getFirmware2()) {
            if (Feature::SECONDARY !== $protocol->getFirmware2()->getFeature()) {
                $this->context->buildViolation($constraint->messageFirmwareInvalidFeature)->atPath('firmware2')->addViolation();
            }

            if ($protocol->getFirmware2()->getDeviceType() !== $deviceType) {
                $this->context->buildViolation($constraint->messageFirmwareInvalidDeviceType)->atPath('firmware2')->addViolation();
            }

            if (!$this->isFirmwareAccessible($protocol->getFirmware2())) {
                $this->context->buildViolation($constraint->messageFirmwareInvalid)->atPath('firmware2')->addViolation();
            }
        }

        if (!$deviceType->getHasFirmware3()) {
            if ($protocol->getFirmware3()) {
                $this->context->buildViolation($constraint->messageFirmware3Disabled)->atPath('firmware3')->addViolation();
            }

            if ($protocol->getReinstallFirmware3()) {
                $this->context->buildViolation($constraint->messageFirmware3Disabled)->atPath('reinstallFirmware3')->addViolation();
            }
        } elseif ($protocol->getFirmware3()) {
            if (Feature::TERTIARY !== $protocol->getFirmware3()->getFeature()) {
                $this->context->buildViolation($constraint->messageFirmwareInvalidFeature)->atPath('firmware3')->addViolation();
            }

            if ($protocol->getFirmware3()->getDeviceType() !== $deviceType) {
                $this->context->buildViolation($constraint->messageFirmwareInvalidDeviceType)->atPath('firmware3')->addViolation();
            }

            if (!$this->isFirmwareAccessible($protocol->getFirmware3())) {
                $this->context->buildViolation($constraint->messageFirmwareInvalid)->atPath('firmware3')->addViolation();
            }
        }
    }

    protected function validateEndpointDevices(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getIsEndpointDevicesAvailable() && $protocol->getVirtualSubnetCidr()) {
            $this->context->buildViolation($constraint->messageVpnDisabled)->atPath('virtualSubnetCidr')->addViolation();
        }

        if (!$deviceType->getIsEndpointDevicesAvailable() && count($protocol->getEndpointDevices()) > 0) {
            $this->context->buildViolation($constraint->messageEndpointDevicesDisabled)->atPath('endpointDevices')->addViolation();
        }
    }
}
