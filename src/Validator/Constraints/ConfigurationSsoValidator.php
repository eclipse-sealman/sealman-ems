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

use App\Enum\MicrosoftOidcCredential;
use App\Enum\SingleSignOn;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\UploadManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConfigurationSsoValidator extends ConstraintValidator
{
    use ConfigurationManagerTrait;

    public function validate($protocol, Constraint $constraint): void
    {
        switch ($protocol->getSingleSignOn()) {
            case SingleSignOn::MICROSOFT_OIDC:
                $this->validateMicrosoftOidc($protocol, $constraint);
                break;
            case SingleSignOn::DISABLED:
            default:
                // Nothing to validate
                break;
        }
    }

    public function validateMicrosoftOidc($protocol, Constraint $constraint)
    {
        if (!$protocol->getMicrosoftOidcAppId()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcAppId')->addViolation();
        }

        if (!$protocol->getMicrosoftOidcDirectoryId()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcDirectoryId')->addViolation();
        }

        if (!$protocol->getMicrosoftOidcCredential()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcCredential')->addViolation();
        }

        if (!$protocol->getMicrosoftOidcTimeout()) {
            $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcTimeout')->addViolation();
        }

        $configuration = $this->getConfiguration();
        $isUploaded = null !== $configuration->getMicrosoftOidcUploadedCertificatePublicThumbprint() ? true : false;
        $isGenerated = null !== $configuration->getMicrosoftOidcGeneratedCertificatePublicThumbprint() ? true : false;

        switch ($protocol->getMicrosoftOidcCredential()) {
            case MicrosoftOidcCredential::CLIENT_SECRET:
                if (!$protocol->getMicrosoftOidcClientSecret()) {
                    $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcClientSecret')->addViolation();
                }
                break;
            case MicrosoftOidcCredential::CERTIFICATE_UPLOAD:
                $require = $isUploaded ? false : true;

                $uploadedPublic = $protocol->getMicrosoftOidcUploadedCertificatePublic();
                $uploadedPrivate = $protocol->getMicrosoftOidcUploadedCertificatePrivate();
                if ($uploadedPublic && UploadManager::isTusUploadedFile($uploadedPublic)) {
                    $require = true;
                }

                if ($uploadedPrivate && UploadManager::isTusUploadedFile($uploadedPrivate)) {
                    $require = true;
                }

                if ($require && !$protocol->getMicrosoftOidcUploadedCertificatePublic()) {
                    $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcUploadedCertificatePublic')->addViolation();
                }
                if ($require && !$protocol->getMicrosoftOidcUploadedCertificatePrivate()) {
                    $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcUploadedCertificatePrivate')->addViolation();
                }
                break;
            case MicrosoftOidcCredential::CERTIFICATE_GENERATE:
                if (!$isGenerated && !$protocol->getMicrosoftOidcGenerateCertificate()) {
                    $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcGenerateCertificate')->addViolation();
                }
                if ($protocol->getMicrosoftOidcGenerateCertificate() && !$protocol->getMicrosoftOidcGenerateCertificateExpiryDays()) {
                    $this->context->buildViolation($constraint->messageRequired)->atPath('microsoftOidcGenerateCertificateExpiryDays')->addViolation();
                }
                break;
        }
    }
}
