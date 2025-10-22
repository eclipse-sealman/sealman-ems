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

namespace App\Trait;

use App\Deny\AbstractApiDuplicateCertificateTypeObjectDeny;
use App\Model\UseableCertificate;
use App\Service\Trait\CertificateTypeHelperTrait;
use Carve\ApiBundle\Deny\DenyInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ApiCertificateTypeHelperTrait
{
    use CertificateTypeHelperTrait;

    /**
     * @var ?DenyInterface
     */
    protected $apiCertificateObject;

    protected function getUsableCertificate(int $id, int $certificateTypeId, string $denyKey): UseableCertificate
    {
        if (!$this->hasDenyClass()) {
            throw new AccessDeniedHttpException();
        }

        $object = $this->find($id, AbstractApiDuplicateCertificateTypeObjectDeny::CERTIFICATE_TYPE);
        $this->setApiCertificateObject($object);

        $certificate = null;
        foreach ($object->getUseableCertificates() as $usableCertificate) {
            if ($usableCertificate->getCertificateType()->getId() == $certificateTypeId) {
                if (!isset($usableCertificate->getDeny()[$denyKey])) {
                    return $usableCertificate;
                } else {
                    throw new AccessDeniedHttpException();
                }
            }
        }

        throw new NotFoundHttpException();
    }

    public function getApiCertificateObject(): ?DenyInterface
    {
        return $this->apiCertificateObject;
    }

    public function setApiCertificateObject(?DenyInterface $apiCertificateObject)
    {
        $this->apiCertificateObject = $apiCertificateObject;
    }
}
