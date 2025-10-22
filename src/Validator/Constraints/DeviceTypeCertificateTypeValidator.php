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

use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DeviceTypeCertificateTypeValidator extends ConstraintValidator
{
    use DeviceCommunicationFactoryTrait;

    // todo handle validation of certificateType hasCertificateType fields to prevent
    // adding new certificateType
    // deleting certificateType
    // changing hasCertificateType value - make sure it is always true on DT edit form
    public function validate($deviceTypeCertificateType, Constraint $constraint): void
    {
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceTypeCertificateType->getDeviceType());
        if ($communicationProcedure) {
            // Validate if certificate categories requirements according to communication procedure

            $communicationCertificateCategoryRequired = $communicationProcedure->getCommunicationProcedureCertificateCategoryRequired();
            $communicationCertificateCategoryOptional = $communicationProcedure->getCommunicationProcedureCertificateCategoryOptional();

            $communicationCertificateCategoryAvailable = array_merge($communicationCertificateCategoryRequired, $communicationCertificateCategoryOptional);

            // todo code base for fix task
            // $certificateTypeList = $this->context->getRoot()->getConfig()->getOption('certificateTypesList');
            // // check if CT is in previous values
            if (null === $deviceTypeCertificateType->getCertificateType()) {
                return;
            }

            if (!in_array($deviceTypeCertificateType->getCertificateType()->getCertificateCategory(), $communicationCertificateCategoryAvailable)) {
                $this->context->buildViolation($constraint->messageCertificateCategoryNotSupported)->atPath('certificateType')->addViolation();
            }
        }
    }
}
