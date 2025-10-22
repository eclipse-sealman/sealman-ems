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
use App\Entity\Device;
use App\Entity\User;
use App\Model\UseableCertificate;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\SimpleDenyManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use Carve\ApiBundle\Deny\DenyInterface;
use Doctrine\Common\Collections\ArrayCollection;

abstract class AbstractApiDuplicateCertificateTypeObjectDeny extends AbstractApiDuplicateObjectDeny
{
    use AuthorizationCheckerTrait;
    use CertificateTypeHelperTrait;
    use ConfigurationManagerTrait;
    // Special trait had to be created due to methods conflicting with AbstractApiDuplicateObjectDeny
    use SimpleDenyManagerTrait;

    // Certificate type deny is used to provide information if any certificate functionalities are available - used as aggregated value for easier code, e.g. in CertificateExpand.tsx
    public const CERTIFICATE_TYPE = 'certificateType';

    public function getValidCertificateTypes(Device|User $object): array
    {
        // check if certificates are available
        if (null !== $this->certificateTypeDeny($object)) {
            return [];
        }

        return $this->getAvailableCertificateTypes($object);
    }

    public function certificateTypeDeny(Device|User $object): ?string
    {
        // for now only Device and User are valid
        if (!$object instanceof Device && !$object instanceof User) {
            return 'accessDenied';
        }

        if ($object instanceof Device) {
            if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
                return 'accessDenied';
            }
            if (!$object->getDeviceType() || !$object->getDeviceType()->getHasCertificates()) {
                return 'disabledInDeviceType';
            }
        }

        if ($object instanceof User) {
            if (!$this->isGranted('ROLE_ADMIN') && ($object !== $this->getUser())) {
                return 'accessDenied';
            }
            // certificates might be assigned manually for smartems users
            if (!$object->getRoleAdmin() && !$object->getRoleVpn() && !$object->getRoleSmartems()) {
                return 'certificateTypeNotApplicable';
            }
        }

        if (0 == count($this->getAvailableCertificateTypes($object))) {
            return 'accessDenied';
        }

        return null;
    }

    public function fillDeny(DenyInterface $object): void
    {
        parent::fillDeny($object);

        if (!$object instanceof Device && !$object instanceof User) {
            return;
        }

        $useableCertificates = new ArrayCollection();

        foreach ($this->getValidCertificateTypes($object) as $certificateType) {
            $certificate = $this->getCertificateByType($object, $certificateType);
            if (!$certificate) {
                // Creating Certificate object for deny calculations
                $certificate = new Certificate();
                if ($object instanceof User) {
                    $certificate->setUser($object);
                }

                if ($object instanceof Device) {
                    $certificate->setDevice($object);
                }

                $certificate->setCertificateType($certificateType);
            }

            $this->denyManager->fillDeny(CertificateDeny::class, $certificate);

            $useableCertificate = new UseableCertificate();

            $useableCertificate->setCertificate($certificate);
            $useableCertificate->setCertificateType($certificateType);
            $useableCertificate->setDeny($certificate->getDeny());

            $useableCertificates->add($useableCertificate);
        }

        $object->setUseableCertificates($useableCertificates);
    }
}
