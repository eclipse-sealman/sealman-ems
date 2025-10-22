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

use App\Enum\CertificateBehavior;
use App\Enum\CertificateCategory;
use App\Enum\PkiType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CertificateTypeValidator extends ConstraintValidator
{
    public function validate($protocol, Constraint $constraint): void
    {
        if ($protocol->getDeleteEnabled() && !$protocol->getUploadEnabled()) {
            $this->context->buildViolation($constraint->messageDeleteRequiresUpload)->atPath('deleteEnabled')->addViolation();
        }

        // If no PKI solution is set, PKI functionalities cannot be used
        if (PkiType::NONE == $protocol->getPkiType()) {
            $predefinedCertificateCategory = $this->context->getRoot()->getConfig()->getOption('predefinedCertificateCategory');

            if (CertificateBehavior::NONE !== $protocol->getEnabledBehaviour()) {
                $this->context->buildViolation($constraint->messagePkiProtocolRequired)->atPath('enabledBehaviour')->addViolation();

                if ($predefinedCertificateCategory) {
                    $this->context->buildViolation($constraint->messagePkiProtocolRequiredByAutomaticBehavior)->atPath('pkiType')->addViolation();
                }
            }

            if (CertificateBehavior::NONE !== $protocol->getDisabledBehaviour()) {
                $this->context->buildViolation($constraint->messagePkiProtocolRequired)->atPath('disabledBehaviour')->addViolation();

                if ($predefinedCertificateCategory) {
                    $this->context->buildViolation($constraint->messagePkiProtocolRequiredByAutomaticBehavior)->atPath('pkiType')->addViolation();
                }
            }

            if ($protocol->getPkiEnabled()) {
                $this->context->buildViolation($constraint->messagePkiProtocolRequired)->atPath('pkiEnabled')->addViolation();
            }
        }

        // DeviceVpn and TechnicianVpn certificate categories have empty variablePrefix for compatibility reasons
        if (CertificateCategory::CUSTOM == $protocol->getCertificateCategory()) {
            if (!$protocol->getCommonNamePrefix()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('commonNamePrefix')->addViolation();
            }
            if (!$protocol->getVariablePrefix()) {
                $this->context->buildViolation($constraint->messageRequired)->atPath('variablePrefix')->addViolation();
            }
        }
    }
}
