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

use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\FieldRequirement;
use App\Enum\MasqueradeType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\UserTrait;
use App\Validator\Constraints\Trait\AccessTagsValidatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Notice! Similar logic is used in ImportDeviceManager. Please be aware of that when adjusting this validator.
 */
class DeviceValidator extends ConstraintValidator
{
    use EntityManagerTrait;
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

        $this->validateName($deviceType, $protocol, $constraint);
        $this->validateSerialNumberAndImsi($deviceType, $protocol, $constraint);
        $this->validateEdgeGatewayFields($deviceType, $protocol, $constraint);
        $this->validateModelField($deviceType, $protocol, $constraint);
        $this->validateRequestDiagnoseData($deviceType, $protocol, $constraint);
        $this->validateRequestConfigData($deviceType, $protocol, $constraint);
        $this->validateStaging($deviceType, $protocol, $constraint);
        $this->validateGsm($deviceType, $protocol, $constraint);
        $this->validateVariables($deviceType, $protocol, $constraint);
        $this->validateMasquerade($deviceType, $protocol, $constraint);
        $this->validateConfigs($deviceType, $protocol, $constraint);
        $this->validateFirmwares($deviceType, $protocol, $constraint);
        $this->validateEndpointDevices($deviceType, $protocol, $constraint);
        $this->validateTemplate($deviceType, $protocol, $constraint);

        // Only ROLE_SMARTEMS role has access tags limited, because it's only role that can use it in form (except of ROLE_ADMIN)
        if ($this->isGranted('ROLE_SMARTEMS')) {
            $this->validateAccessTags($protocol);
        }
    }

    protected function validateName(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if ($this->isFieldDuplicated($deviceType, $protocol->getName(), 'name', $protocol->getId())) {
            $this->context->buildViolation($constraint->messageNameNotUnique)->atPath('name')->addViolation();
        }
    }

    protected function validateSerialNumberAndImsi(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (FieldRequirement::REQUIRED == $deviceType->getFieldSerialNumber() && !$protocol->getSerialNumber()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('serialNumber')->addViolation();
        }

        if (FieldRequirement::REQUIRED == $deviceType->getFieldSerialNumber()) {
            if ($this->isFieldDuplicated($deviceType, $protocol->getSerialNumber(), 'serialNumber', $protocol->getId())) {
                $this->context->buildViolation($constraint->messageFieldNotUnique)->atPath('serialNumber')->addViolation();
            }
        }

        if (FieldRequirement::REQUIRED == $deviceType->getFieldImsi() && !$protocol->getImsi()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('imsi')->addViolation();
        }

        if (FieldRequirement::REQUIRED == $deviceType->getFieldImsi()) {
            if ($this->isFieldDuplicated($deviceType, $protocol->getImsi(), 'imsi', $protocol->getId())) {
                $this->context->buildViolation($constraint->messageFieldNotUnique)->atPath('imsi')->addViolation();
            }
        }
    }

    protected function validateEdgeGatewayFields(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (FieldRequirement::REQUIRED == $deviceType->getFieldRegistrationId() && !$protocol->getRegistrationId()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('registrationId')->addViolation();
        }

        if (FieldRequirement::REQUIRED == $deviceType->getFieldRegistrationId()) {
            if ($this->isFieldDuplicated($deviceType, $protocol->getRegistrationId(), 'registrationId', $protocol->getId())) {
                $this->context->buildViolation($constraint->messageFieldNotUnique)->atPath('registrationId')->addViolation();
            }
        }

        if (FieldRequirement::REQUIRED == $deviceType->getFieldEndorsementKey() && !$protocol->getEndorsementKey()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('endorsementKey')->addViolation();
        }

        if (FieldRequirement::REQUIRED == $deviceType->getFieldEndorsementKey()) {
            if ($this->isFieldDuplicated($deviceType, $protocol->getEndorsementKey(), 'endorsementKey', $protocol->getId())) {
                $this->context->buildViolation($constraint->messageFieldNotUnique)->atPath('endorsementKey')->addViolation();
            }
        }

        if (FieldRequirement::REQUIRED == $deviceType->getFieldHardwareVersion() && !$protocol->getHardwareVersion()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('hardwareVersion')->addViolation();
        }
    }

    protected function validateModelField(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (FieldRequirement::REQUIRED == $deviceType->getFieldModel() && !$protocol->getModel()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('model')->addViolation();
        }
    }

    protected function validateRequestDiagnoseData(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getHasRequestDiagnose() && $protocol->getRequestDiagnoseData()) {
            $this->context->buildViolation($constraint->messageRequestDiagnoseDataDisabled)->atPath('requestDiagnoseData')->addViolation();
        }
    }

    protected function validateRequestConfigData(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getHasRequestConfig() && $protocol->getRequestConfigData()) {
            $this->context->buildViolation($constraint->messageRequestConfigDataDisabled)->atPath('requestConfigData')->addViolation();
        }
    }

    protected function validateStaging(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getHasTemplates() && $protocol->getStaging()) {
            $this->context->buildViolation($constraint->messageTemplatesDisabled)->atPath('staging')->addViolation();
        }
    }

    protected function validateGsm(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getHasGsm() && $protocol->getImsi()) {
            $this->context->buildViolation($constraint->messageGsmDisabled)->atPath('imsi')->addViolation();
        }

        if (!$deviceType->getHasGsm() && $protocol->getImei()) {
            $this->context->buildViolation($constraint->messageGsmDisabled)->atPath('imei')->addViolation();
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
        if (!$this->isGranted('ROLE_ADMIN_VPN')) {
            // $masquerades and $masqueradeType fields has been removed. Skip validation
            return;
        }

        if (!$deviceType->getIsMasqueradeAvailable()) {
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
                $this->context->buildViolation($constraint->messageRequired)->atPath('masqueradeType')->addViolation();
                break;
        }
    }

    protected function validateConfigs(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        // Decided not to check hasAlwaysReinstallConfig flag for easier UX during API call
        // Cleanup code added to controller
        if (!$deviceType->getHasConfig1() && $protocol->getReinstallConfig1()) {
            $this->context->buildViolation($constraint->messageConfig1Disabled)->atPath('reinstallConfig1')->addViolation();
        }

        if (!$deviceType->getHasConfig2() && $protocol->getReinstallConfig2()) {
            $this->context->buildViolation($constraint->messageConfig2Disabled)->atPath('reinstallConfig2')->addViolation();
        }

        if (!$deviceType->getHasConfig3() && $protocol->getReinstallConfig3()) {
            $this->context->buildViolation($constraint->messageConfig3Disabled)->atPath('reinstallConfig3')->addViolation();
        }
    }

    protected function validateFirmwares(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$deviceType->getHasFirmware1() && $protocol->getReinstallFirmware1()) {
            $this->context->buildViolation($constraint->messageFirmware1Disabled)->atPath('reinstallFirmware1')->addViolation();
        }

        if (!$deviceType->getHasFirmware2() && $protocol->getReinstallFirmware2()) {
            $this->context->buildViolation($constraint->messageFirmware2Disabled)->atPath('reinstallFirmware2')->addViolation();
        }

        if (!$deviceType->getHasFirmware3() && $protocol->getReinstallFirmware3()) {
            $this->context->buildViolation($constraint->messageFirmware3Disabled)->atPath('reinstallFirmware3')->addViolation();
        }
    }

    protected function validateEndpointDevices(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$this->isGranted('ROLE_ADMIN_VPN')) {
            // $virtualSubnetCidr and endpointDevices field has been removed. Skip validation
            return;
        }

        if ($deviceType->getIsEndpointDevicesAvailable() && !$protocol->getVirtualSubnetCidr()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('virtualSubnetCidr')->addViolation();
        }

        if (!$deviceType->getIsEndpointDevicesAvailable() && count($protocol->getEndpointDevices()) > 0) {
            $this->context->buildViolation($constraint->messageEndpointDevicesDisabled)->atPath('endpointDevices')->addViolation();
        }
    }

    protected function validateTemplate(DeviceType $deviceType, $protocol, Constraint $constraint): void
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            // $template field has been removed. Skip validation
            return;
        }

        $template = $protocol->getTemplate();
        if (!$template) {
            return;
        }

        if (!$deviceType->getHasTemplates()) {
            $this->context->buildViolation($constraint->messageTemplatesDisabled)->atPath('template')->addViolation();

            return;
        }

        if ($template->getDeviceType() !== $deviceType) {
            $this->context->buildViolation($constraint->messageTemplateDeviceTypeMismatch)->atPath('template')->addViolation();
        }

        if (!$this->isTemplateAccessible($template)) {
            $this->context->buildViolation($constraint->messageInvalidChoice)->atPath('template')->addViolation();
        }
    }

    protected function isFieldDuplicated(DeviceType $deviceType, mixed $value, string $fieldName, ?int $id = null): bool
    {
        $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');
        $queryBuilder->andWhere('d.deviceType = :deviceType');
        $queryBuilder->andWhere('d.'.$fieldName.' = :value');
        $queryBuilder->setParameter('value', $value);
        $queryBuilder->setParameter('deviceType', $deviceType);

        if ($id) {
            $queryBuilder->andWhere('d.id != :id');
            $queryBuilder->setParameter('id', $id);
        }

        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult() ? true : false;
    }
}
