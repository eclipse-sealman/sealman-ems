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

namespace App\Deny;

use App\Entity\Certificate;
use App\Enum\CertificateCategory;
use App\Enum\PkiType;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\UserTrait;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;

class CertificateDeny extends AbstractApiObjectDeny implements CertificateDenyInterface
{
    use AuthorizationCheckerTrait;
    use ConfigurationManagerTrait;
    use UserTrait;

    public function deleteCertificateDeny(Certificate $object): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getDeleteEnabled()) {
            return 'accessDenied';
        }

        if (!$object->hasAnyCertificatePart()) {
            return 'noCertificate';
        }

        if ($object->getCertificateGenerated()) {
            return 'pkiGeneratedCertificate';
        }

        return null;
    }

    public function generateCertificateDeny(Certificate $object): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN_SCEP')) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getPkiEnabled() || PkiType::NONE === $object->getCertificateType()->getPkiType()) {
            return 'accessDenied';
        }

        // Specific TECHNICIAN_VPN role SmartEMS limitations
        if (CertificateCategory::TECHNICIAN_VPN == $object->getCertificateType()->getCertificateCategory()) {
            if ($object->getUser() && $object->getUser()->getRoleSmartems() && !$object->getUser()->getRoleVpn()) {
                return 'roleNotSupported';
            }
        }

        if ($object->hasAnyCertificatePart()) {
            return 'hasCertificate';
        }

        return null;
    }

    public function revokeCertificateDeny(Certificate $object): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN_SCEP')) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getPkiEnabled() || PkiType::NONE === $object->getCertificateType()->getPkiType()) {
            return 'accessDenied';
        }

        // Specific TECHNICIAN_VPN role SmartEMS limitations
        if (CertificateCategory::TECHNICIAN_VPN == $object->getCertificateType()->getCertificateCategory()) {
            if ($object->getUser() && $object->getUser()->getRoleSmartems() && !$object->getUser()->getRoleVpn()) {
                return 'roleNotSupported';
            }
        }

        if (!$object->hasCertificate()) {
            return 'noCertificate';
        }

        if (!$object->getCertificateGenerated()) {
            return 'notPkiGeneratedCertificate';
        }

        return null;
    }

    public function downloadCertificateDeny(Certificate $object): ?string
    {
        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getDownloadEnabled() && $object->getUser() !== $this->getUser()) {
            return 'accessDenied';
        }

        if (!$object->getCertificate()) {
            return 'noCertificate';
        }

        return null;
    }

    public function downloadCaCertificateDeny(Certificate $object): ?string
    {
        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getDownloadEnabled() && $object->getUser() !== $this->getUser()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateCa()) {
            return 'noCaCertificate';
        }

        return null;
    }

    public function downloadPrivateKeyDeny(Certificate $object): ?string
    {
        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getDownloadEnabled() && $object->getUser() !== $this->getUser()) {
            return 'accessDenied';
        }

        if (!$object->getPrivateKey()) {
            return 'noPrivateKey';
        }

        return null;
    }

    public function downloadPkcs12Deny(Certificate $object): ?string
    {
        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getDownloadEnabled() && $object->getUser() !== $this->getUser()) {
            return 'accessDenied';
        }

        if (!$object->getCertificate()) {
            return 'noCertificate';
        }

        if (!$object->getPrivateKey()) {
            return 'noPrivateKey';
        }

        return null;
    }

    public function uploadCertificatesDeny(Certificate $object): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getUploadEnabled()) {
            return 'accessDenied';
        }

        if ($object->hasAnyCertificatePart()) {
            return 'hasCertificate';
        }

        return null;
    }

    public function uploadPkcs12Deny(Certificate $object): ?string
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getIsAvailable()) {
            return 'accessDenied';
        }

        if (!$object->getCertificateType()->getUploadEnabled()) {
            return 'accessDenied';
        }

        if ($object->hasAnyCertificatePart()) {
            return 'hasCertificate';
        }

        return null;
    }
}
