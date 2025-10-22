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

namespace App\Entity;

use App\Enum\AltNameType;
use App\Enum\CertificateEncoding;
use App\Model\AuditableInterface;
use App\Validator\Constraints\DeviceTypeCertificateType as DeviceTypeCertificateTypeValidator;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[DeviceTypeCertificateTypeValidator(groups: ['deviceType:common'])]
class DeviceTypeCertificateType implements AuditableInterface
{
    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Certificate type.
     */
    #[Groups(['deviceType:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\ManyToOne(targetEntity: CertificateType::class, inversedBy: 'deviceTypeCertificateTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CertificateType $certificateType = null;

    /**
     * Enable automatic certificate renewal close to expiration during device communication.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enableCertificatesAutoRenew = false;

    /**
     * How many days before expiration certificate should be renewed.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['deviceType:common'])]
    #[Assert\NotBlankOnTrue(propertyPath: 'enableCertificatesAutoRenew', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $certificatesAutoRenewDaysBefore = 14;

    /**
     * Enables custom subject alternate name for SSL certificates during PKI generation (auto renewal too).
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enableSubjectAltName = false;

    /**
     * Subject alternate name type for SSL certificate.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'enableSubjectAltName', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: AltNameType::class, nullable: true)]
    private ?AltNameType $subjectAltNameType = AltNameType::DNS;

    /**
     * Subject alternate name value for SSL certificate.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlankOnTrue(propertyPath: 'enableSubjectAltName', groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $subjectAltNameValue = null;

    /**
     * Type of certificate encoding while sending to physical device.
     * Property is nullable to facilitate possibility of deviceType not using this functionality
     * When property is used checking for null is required.
     */
    #[Groups(['deviceType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: CertificateEncoding::class)]
    private ?CertificateEncoding $certificateEncoding = CertificateEncoding::HEX;

    /**
     * Helper field used to provide information if certificate of this certificateType are available for this device type (depending on license and system state).
     */
    #[Groups(['deviceType:public', 'device:public'])]
    private ?bool $isCertificateTypeAvailable = false;

    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class, inversedBy: 'certificateTypes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getCertificateType(): ?CertificateType
    {
        return $this->certificateType;
    }

    public function setCertificateType(?CertificateType $certificateType)
    {
        $this->certificateType = $certificateType;
    }

    public function getEnableCertificatesAutoRenew(): ?bool
    {
        return $this->enableCertificatesAutoRenew;
    }

    public function setEnableCertificatesAutoRenew(?bool $enableCertificatesAutoRenew)
    {
        $this->enableCertificatesAutoRenew = $enableCertificatesAutoRenew;
    }

    public function getCertificatesAutoRenewDaysBefore(): ?int
    {
        return $this->certificatesAutoRenewDaysBefore;
    }

    public function setCertificatesAutoRenewDaysBefore(?int $certificatesAutoRenewDaysBefore)
    {
        $this->certificatesAutoRenewDaysBefore = $certificatesAutoRenewDaysBefore;
    }

    public function getEnableSubjectAltName(): ?bool
    {
        return $this->enableSubjectAltName;
    }

    public function setEnableSubjectAltName(?bool $enableSubjectAltName)
    {
        $this->enableSubjectAltName = $enableSubjectAltName;
    }

    public function getSubjectAltNameType(): ?AltNameType
    {
        return $this->subjectAltNameType;
    }

    public function setSubjectAltNameType(?AltNameType $subjectAltNameType)
    {
        $this->subjectAltNameType = $subjectAltNameType;
    }

    public function getSubjectAltNameValue(): ?string
    {
        return $this->subjectAltNameValue;
    }

    public function setSubjectAltNameValue(?string $subjectAltNameValue)
    {
        $this->subjectAltNameValue = $subjectAltNameValue;
    }

    public function getIsCertificateTypeAvailable(): ?bool
    {
        return $this->isCertificateTypeAvailable;
    }

    public function setIsCertificateTypeAvailable(?bool $isCertificateTypeAvailable)
    {
        $this->isCertificateTypeAvailable = $isCertificateTypeAvailable;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getCertificateEncoding(): ?CertificateEncoding
    {
        return $this->certificateEncoding;
    }

    public function setCertificateEncoding(?CertificateEncoding $certificateEncoding)
    {
        $this->certificateEncoding = $certificateEncoding;
    }
}
