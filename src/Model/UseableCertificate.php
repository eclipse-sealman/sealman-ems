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

namespace App\Model;

use App\Entity\Certificate;
use App\Entity\CertificateType;
use App\Validator\Constraints\CertificateBehavior;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Symfony\Component\Serializer\Annotation\Groups;

#[CertificateBehavior(groups: ['device:common', 'user:certificateBehaviours'])]
class UseableCertificate implements DenyInterface
{
    use DenyTrait;

    /**
     * Certificate type.
     */
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private'])]
    private ?CertificateType $certificateType = null;

    /**
     * Certificate.
     */
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private'])]
    private ?Certificate $certificate = null;

    /**
     * Should certificate be generated when enabling? Can be set to true only when VPN functionality is enabled and only for devices with VPN and users with Admin or VPN permissions. Already generated certificate will not be generated again.
     */
    private ?bool $generateCertificate = null;

    /**
     * Should certificate be revoked when disabling? Can be set to true only when VPN functionality is enabled and only for devices with VPN and users with Admin or VPN permissions.
     */
    private ?bool $revokeCertificate = null;

    public function getCertificateType(): ?CertificateType
    {
        return $this->certificateType;
    }

    public function setCertificateType(?CertificateType $certificateType)
    {
        $this->certificateType = $certificateType;
    }

    public function getCertificate(): ?Certificate
    {
        return $this->certificate;
    }

    public function setCertificate(?Certificate $certificate)
    {
        $this->certificate = $certificate;
    }

    public function getGenerateCertificate(): ?bool
    {
        return $this->generateCertificate;
    }

    public function setGenerateCertificate(?bool $generateCertificate)
    {
        $this->generateCertificate = $generateCertificate;
    }

    public function getRevokeCertificate(): ?bool
    {
        return $this->revokeCertificate;
    }

    public function setRevokeCertificate(?bool $revokeCertificate)
    {
        $this->revokeCertificate = $revokeCertificate;
    }
}
