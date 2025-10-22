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

namespace App\Entity\Traits;

use App\Model\AuditableInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;


trait CertificateDataEntityTrait
{
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $certificateSubject = null;

    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $certificate = null;

    #[Groups(['certificate:private'])]
    #[SerializedName('certificate')]
    private ?string $decryptedCertificate = null;

    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $privateKey = null;

    #[Groups(['certificate:private'])]
    #[SerializedName('privateKey')]
    private ?string $decryptedPrivateKey = null;

    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $certificateCa = null;

    #[Groups(['certificate:private'])]
    #[SerializedName('certificateCa')]
    private ?string $decryptedCertificateCa = null;

    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $certificateCaSubject = null;

    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $certificateValidTo = null;

    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $pkcsPrivateKeyPassword = null;

    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:private', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $certificateGenerated = false;

    public function getCertificateSubjectSanitized(): ?string
    {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower($this->getCertificateSubject()));
    }

    // Serialize wierdness
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private'])]
    public function getHasCertificate(): bool
    {
        return $this->hasCertificate();
    }

    public function hasCertificate(): bool
    {
        return $this->getCertificate() && $this->getCertificateCa() && $this->getPrivateKey();
    }

    public function hasAnyCertificatePart(): bool
    {
        return $this->getCertificate() || $this->getCertificateCa() || $this->getPrivateKey();
    }

    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private'])]
    public function getIsCertificateExpired(): ?bool
    {
        if (!$this->getCertificate()) {
            return null;
        }

        $now = new \DateTime();

        return $this->getCertificateValidTo() && $now > $this->getCertificateValidTo();
    }

    public function getCertificateSubject(): ?string
    {
        return $this->certificateSubject;
    }

    public function setCertificateSubject(?string $certificateSubject)
    {
        $this->certificateSubject = $certificateSubject;
    }

    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    public function setCertificate(?string $certificate)
    {
        $this->certificate = $certificate;
    }

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function setPrivateKey(?string $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    public function getCertificateCa(): ?string
    {
        return $this->certificateCa;
    }

    public function setCertificateCa(?string $certificateCa)
    {
        $this->certificateCa = $certificateCa;
    }

    public function getCertificateCaSubject(): ?string
    {
        return $this->certificateCaSubject;
    }

    public function setCertificateCaSubject(?string $certificateCaSubject)
    {
        $this->certificateCaSubject = $certificateCaSubject;
    }

    public function getCertificateValidTo(): ?\DateTime
    {
        return $this->certificateValidTo;
    }

    public function setCertificateValidTo(?\DateTime $certificateValidTo)
    {
        $this->certificateValidTo = $certificateValidTo;
    }

    public function getPkcsPrivateKeyPassword(): ?string
    {
        return $this->pkcsPrivateKeyPassword;
    }

    public function setPkcsPrivateKeyPassword(?string $pkcsPrivateKeyPassword)
    {
        $this->pkcsPrivateKeyPassword = $pkcsPrivateKeyPassword;
    }

    public function getCertificateGenerated(): ?bool
    {
        return $this->certificateGenerated;
    }

    public function setCertificateGenerated(?bool $certificateGenerated)
    {
        $this->certificateGenerated = $certificateGenerated;
    }

    public function getDecryptedCertificate(): ?string
    {
        return $this->decryptedCertificate;
    }

    public function setDecryptedCertificate(?string $decryptedCertificate)
    {
        $this->decryptedCertificate = $decryptedCertificate;
    }

    public function getDecryptedPrivateKey(): ?string
    {
        return $this->decryptedPrivateKey;
    }

    public function setDecryptedPrivateKey(?string $decryptedPrivateKey)
    {
        $this->decryptedPrivateKey = $decryptedPrivateKey;
    }

    public function getDecryptedCertificateCa(): ?string
    {
        return $this->decryptedCertificateCa;
    }

    public function setDecryptedCertificateCa(?string $decryptedCertificateCa)
    {
        $this->decryptedCertificateCa = $decryptedCertificateCa;
    }
}
