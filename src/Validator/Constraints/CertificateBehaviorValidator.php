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

use App\Entity\CertificateType;
use App\Entity\Device;
use App\Entity\User;
use App\Enum\CertificateBehavior;
use App\Enum\CertificateCategory;
use App\Model\UseableCertificate;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CertificateBehaviorValidator extends ConstraintValidator
{
    use CertificateTypeHelperTrait;
    use ConfigurationManagerTrait;

    // If new specific behavior will be added focus on updating validateSpecificEnabledBehaviour and validateSpecificDisabledBehaviour
    public function validate($protocol, Constraint $constraint): void
    {
        // Getting parent of this Certificate object
        $parentObject = $this->context->getRoot()->getData();

        if (!$parentObject || !$protocol->getCertificateType() || !$protocol->getCertificateType()->getIsAvailable()) {
            $this->context->buildViolation($constraint->messageInvalid)->atPath('revokeCertificate')->addViolation();
            $this->context->buildViolation($constraint->messageInvalid)->atPath('generateCertificate')->addViolation();

            return;
        }

        if (!$parentObject instanceof Device && !$parentObject instanceof User) {
            // Cannot use Symfony\Component\Validator\Exception\UnexpectedValueException due to two accepted types
            throw new \Exception('CertificateBehaviorValidator only supports '.Device::class.' and '.User::class);
        }

        // Check if CertificateType provided in certificateBehaviours is available in useableCertificates (which is set by deny class)
        if (!$this->getUseableCertificate($protocol->getCertificateType(), $parentObject)) {
            $this->context->buildViolation($constraint->messageInvalid)->atPath('revokeCertificate')->addViolation();
            $this->context->buildViolation($constraint->messageInvalid)->atPath('generateCertificate')->addViolation();

            return;
        }

        if ($parentObject->getEnabled()) {
            // Revoke flag is not valid in this case
            if ($protocol->getRevokeCertificate()) {
                $this->context->buildViolation($constraint->messageRevokeCertificateDisabledRequired)->atPath('revokeCertificate')->addViolation();
            }

            // Automatic generation behavior
            $this->validateEnabledBehaviour($parentObject, $protocol, $constraint);
        } else {
            // Generate flag is not valid in this case
            if ($protocol->getGenerateCertificate()) {
                $this->context->buildViolation($constraint->messageGenerateCertificateEnabledRequired)->atPath('generateCertificate')->addViolation();
            }

            // Automatic revocation behavior
            $this->validateDisabledBehaviour($parentObject, $protocol, $constraint);
        }
    }

    protected function getUseableCertificate(CertificateType $certificateType, Device|User $parentObject): ?UseableCertificate
    {
        // this means object is being edited and $useableCertificates are already set by deny
        if ($parentObject->getId()) {
            foreach ($parentObject->getUseableCertificates() as $useableCertificate) {
                if ($useableCertificate->getCertificateType()->getId() === $certificateType->getId()) {
                    return $useableCertificate;
                }
            }
        } else {
            // Method getAvailableCertificateTypes will return all available certificateTypes when useableCertificate are not available due to Create action
            foreach ($this->getAvailableCertificateTypes($parentObject) as $availableCertificateType) {
                if ($availableCertificateType->getId() === $certificateType->getId()) {
                    $useableCertificate = new UseableCertificate();
                    $useableCertificate->setCertificateType($availableCertificateType);

                    return $useableCertificate;
                }
            }
        }

        return null;
    }

    protected function validateEnabledBehaviour(Device|User $parentObject, $protocol, Constraint $constraint)
    {
        switch ($protocol->getCertificateType()->getEnabledBehaviour()) {
            case CertificateBehavior::NONE:
            case CertificateBehavior::AUTO:
                // In case of None or Auto generateCertificate flag is not supported
                // In case of Auto validateIfCertificateCanBeGenerated is not validated. If generation will not be possible VpnManager will handle it
                if ($protocol->getGenerateCertificate()) {
                    $this->context->buildViolation($constraint->messageNotSupportedByCertificateBehavior)->atPath('generateCertificate')->addViolation();
                }
                break;
            case CertificateBehavior::ON_DEMAND:
                $this->validateIfCertificateCanBeGenerated($protocol, $constraint);
                break;
            case CertificateBehavior::SPECIFIC:
                if ($this->validateIfCertificateCanBeGenerated($protocol, $constraint)) {
                    $this->validateSpecificEnabledBehaviour($parentObject, $protocol, $constraint);
                }
                break;
        }
    }

    // Returns true if certificate can be generated
    protected function validateIfCertificateCanBeGenerated($protocol, Constraint $constraint): bool
    {
        // GenerateCertificate flag can be set only if SCEP is available for this certificate type (configuration and license)
        if ($protocol->getGenerateCertificate()) {
            if (!$this->isCertificateTypePkiAvailable($protocol->getCertificateType())) {
                $this->context->buildViolation($constraint->messagePkiNotAvailable)->atPath('generateCertificate')->addViolation();

                return false;
            }
        }

        return true;
    }

    // Method assumes that certificate can be generated and just checks specific conditions
    protected function validateSpecificEnabledBehaviour(Device|User $parentObject, $protocol, Constraint $constraint)
    {
        switch ($protocol->getCertificateType()->getCertificateCategory()) {
            case CertificateCategory::DEVICE_VPN:
                $this->validateDeviceVpnSpecificEnabledBehaviour($parentObject, $protocol, $constraint);
                break;
            case CertificateCategory::TECHNICIAN_VPN:
                $this->validateTechnicianVpnSpecificEnabledBehaviour($parentObject, $protocol, $constraint);
                break;
        }
    }

    protected function validateDeviceVpnSpecificEnabledBehaviour(Device $parentObject, $protocol, Constraint $constraint)
    {
        // In this case deviceVpn certificateCategory acts like it would be set to AUTO
        if ($protocol->getGenerateCertificate()) {
            $this->context->buildViolation($constraint->messageNotSupportedByCertificateBehavior)->atPath('generateCertificate')->addViolation();
        }
    }

    protected function validateTechnicianVpnSpecificEnabledBehaviour(User $parentObject, $protocol, Constraint $constraint)
    {
        if ($protocol->getGenerateCertificate()) {
            if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
                $this->context->buildViolation($constraint->messageVpnNotAvailable)->atPath('generateCertificate')->addViolation();

                return;
            }
            // roleSmartems cannot generate certificates (only if combined with roleVpn)
            if ($parentObject->getRoleSmartems() && !$parentObject->getRoleVpn()) {
                $this->context->buildViolation($constraint->messageGenerateCertificateRoleSmartemsNotSupported)->atPath('generateCertificate')->addViolation();
            }
        }
    }

    protected function validateDisabledBehaviour(Device|User $parentObject, $protocol, Constraint $constraint)
    {
        switch ($protocol->getCertificateType()->getDisabledBehaviour()) {
            case CertificateBehavior::NONE:
            case CertificateBehavior::AUTO:
                // In case of None or Auto revokeCertificate flag is not supported
                // In case of Auto validateIfCertificateCanBeRevoked is not validated. If revocation will not be possible VpnManager will handle it
                if ($protocol->getRevokeCertificate()) {
                    $this->context->buildViolation($constraint->messageNotSupportedByCertificateBehavior)->atPath('revokeCertificate')->addViolation();
                }

                break;
            case CertificateBehavior::ON_DEMAND:
                $this->validateIfCertificateCanBeRevoked($parentObject, $protocol, $constraint);
                break;
            case CertificateBehavior::SPECIFIC:
                $this->validateSpecificDisabledBehaviour($parentObject, $protocol, $constraint);
                break;
        }
    }

    // Returns true if certificate can be revoked
    protected function validateIfCertificateCanBeRevoked(Device|User $parentObject, $protocol, Constraint $constraint): bool
    {
        // RevokeCertificate flag can be set only if SCEP is available for this certificate type (configuration and license)
        if ($protocol->getRevokeCertificate()) {
            if (!$this->isCertificateTypePkiAvailable($protocol->getCertificateType())) {
                $this->context->buildViolation($constraint->messagePkiNotAvailable)->atPath('revokeCertificate')->addViolation();

                return false;
            }

            // this has to return valid value, because same check is performed at begining of this validation
            $useableCertificate = $this->getUseableCertificate($protocol->getCertificateType(), $parentObject);

            // Certificate needs to be available for revocation
            if (!$useableCertificate->getCertificate() || !$useableCertificate->getCertificate()->getCertificateGenerated()) {
                $this->context->buildViolation($constraint->messageCertificateNotGenerated)->atPath('revokeCertificate')->addViolation();

                return false;
            }
        }

        return true;
    }

    protected function validateSpecificDisabledBehaviour(Device|User $parentObject, $protocol, Constraint $constraint)
    {
        if (!$this->validateIfCertificateCanBeRevoked($parentObject, $protocol, $constraint)) {
            return;
        }

        switch ($protocol->getCertificateType()->getCertificateCategory()) {
            case CertificateCategory::DEVICE_VPN:
                $this->validateDeviceVpnSpecificDisabledBehaviour($parentObject, $protocol, $constraint);
                break;
            case CertificateCategory::TECHNICIAN_VPN:
                $this->validateTechnicianVpnSpecificDisabledBehaviour($parentObject, $protocol, $constraint);
                break;
        }
    }

    // If Device or User will use invalid certificate type - PHP will throw type exception
    protected function validateDeviceVpnSpecificDisabledBehaviour(Device $parentObject, $protocol, Constraint $constraint)
    {
        if ($protocol->getRevokeCertificate()) {
            if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
                $this->context->buildViolation($constraint->messageVpnNotAvailable)->atPath('revokeCertificate')->addViolation();

                return;
            }

            $deviceType = $parentObject->getDeviceType();
            $deviceTypeCertificateType = $this->getDeviceTypeCertificateTypeByType($deviceType, $protocol->getCertificateType());

            if (!$deviceTypeCertificateType || !$deviceType || !$deviceType->getHasCertificates()) {
                $this->context->buildViolation($constraint->messageNotSupportedByCertificateBehavior)->atPath('revokeCertificate')->addViolation();
            }
        }
    }

    // If Device or User will use invalid certificate type - PHP will throw type exception
    protected function validateTechnicianVpnSpecificDisabledBehaviour(User $parentObject, $protocol, Constraint $constraint)
    {
        if ($protocol->getRevokeCertificate()) {
            if (!$this->configurationManager->isVpnSecuritySuiteAvailable()) {
                $this->context->buildViolation($constraint->messageVpnNotAvailable)->atPath('revokeCertificate')->addViolation();

                return;
            }

            // roleSmartems cannot revoke certificates (only if combined with roleVpn)
            if ($parentObject->getRoleSmartems() && !$parentObject->getRoleVpn()) {
                $this->context->buildViolation($constraint->messageRevokeCertificateRoleSmartemsNotSupported)->atPath('revokeCertificate')->addViolation();
            }
        }
    }
}
