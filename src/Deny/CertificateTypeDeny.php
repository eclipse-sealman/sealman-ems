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

use App\Entity\CertificateType;
use App\Enum\CertificateCategory;
use App\Service\Trait\CertificateTypeHelperTrait;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;

class CertificateTypeDeny extends AbstractApiObjectDeny
{
    use CertificateTypeHelperTrait;

    public const IS_AVAILABLE = 'isAvailable';

    // Method used for isAvailable column tooltip (reason text if not available)
    public function isAvailableDeny(CertificateType $object): ?string
    {
        return $this->getCertificateTypeAvailableDeny($object);
    }

    public function deleteDeny(CertificateType $object): ?string
    {
        if (CertificateCategory::CUSTOM !== $object->getCertificateCategory()) {
            return 'predefinedCertificateTypeCannotBeDeleted';
        }

        foreach ($object->getCertificates() as $certificate) {
            if ($certificate->hasAnyCertificatePart()) {
                return 'certificateTypeInUse';
            }
        }

        if ($object->getDeviceTypeCertificateTypes()->count() > 0) {
            return 'certificateTypeInUseByDeviceType';
        }

        if ($object->getDeviceTypeCertificateTypeCredentials()->count() > 0) {
            return 'certificateTypeInUseByDeviceType';
        }

        return null;
    }
}
