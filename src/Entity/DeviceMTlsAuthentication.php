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

use App\Entity\Traits\BlameableEntityInterface;
use App\Entity\Traits\BlameableEntityTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Enum\CrlType;
use App\Model\AuditableInterface;
use App\Validator\Constraints\DeviceMTlsAuthentication as DeviceMTlsAuthenticationValidator;
use App\Validator\Constraints\X509Ca;
use App\Validator\Constraints\X509Crl;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[DeviceMTlsAuthenticationValidator(groups: ['deviceMTlsAuthentication:common'])]
class DeviceMTlsAuthentication implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Name of mTLS authentication credentials.
     */
    #[Groups(['deviceMTlsAuthentication:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceMTlsAuthentication:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * CA certificate content used in mTLS authentication.
     */
    #[Groups(['deviceMTlsAuthentication:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceMTlsAuthentication:common'])]
    #[X509Ca(groups: ['deviceMTlsAuthentication:common'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $certificateCa = null;

    /**
     * CA certificate subject.
     */
    #[Groups(['deviceMTlsAuthentication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $certificateCaSubject = null;

    /**
     * CA certificate valid to.
     */
    #[Groups(['deviceMTlsAuthentication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $certificateCaValidTo = null;

    /**
     * CRL certificate type.
     */
    #[Groups(['deviceMTlsAuthentication:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['deviceMTlsAuthentication:common'])]
    #[ORM\Column(type: Types::STRING, enumType: CrlType::class)]
    private ?CrlType $crlType = CrlType::NONE;

    /**
     * CRL certificate content.
     */
    #[Groups(['deviceMTlsAuthentication:public', AuditableInterface::GROUP])]
    #[X509Crl(groups: ['deviceMTlsAuthentication:common'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $certificateCrl = null;

    /**
     * CRL url.
     */
    #[Groups(['deviceMTlsAuthentication:public', AuditableInterface::GROUP])]
    #[Assert\Url(groups: ['deviceMTlsAuthentication:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $certificateCrlUrl = null;

    #[Groups(['deviceMTlsAuthentication:public', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'deviceMTlsAuthentications', targetEntity: DeviceType::class)]
    private Collection $deviceTypes;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function addDeviceType(DeviceType $deviceType)
    {
        if (!$this->deviceTypes->contains($deviceType)) {
            $this->deviceTypes->add($deviceType);
            $deviceType->addDeviceMTlsAuthentication($this);
        }
    }

    public function removeDeviceType(DeviceType $deviceType)
    {
        if ($this->deviceTypes->contains($deviceType)) {
            $this->deviceTypes->removeElement($deviceType);
            $deviceType->removeDeviceMTlsAuthentication($this);
        }
    }

    public function __construct()
    {
        $this->deviceTypes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getCertificateCa(): ?string
    {
        return $this->certificateCa;
    }

    public function setCertificateCa(?string $certificateCa)
    {
        $this->certificateCa = $certificateCa;
    }

    public function getDecryptedCertificateCa(): ?string
    {
        return $this->decryptedCertificateCa;
    }

    public function setDecryptedCertificateCa(?string $decryptedCertificateCa)
    {
        $this->decryptedCertificateCa = $decryptedCertificateCa;
    }

    public function getCertificateCaSubject(): ?string
    {
        return $this->certificateCaSubject;
    }

    public function setCertificateCaSubject(?string $certificateCaSubject)
    {
        $this->certificateCaSubject = $certificateCaSubject;
    }

    public function getCertificateCaValidTo(): ?\DateTime
    {
        return $this->certificateCaValidTo;
    }

    public function setCertificateCaValidTo(?\DateTime $certificateCaValidTo)
    {
        $this->certificateCaValidTo = $certificateCaValidTo;
    }

    public function getCrlType(): ?CrlType
    {
        return $this->crlType;
    }

    public function setCrlType(?CrlType $crlType)
    {
        $this->crlType = $crlType;
    }

    public function getCertificateCrl(): ?string
    {
        return $this->certificateCrl;
    }

    public function setCertificateCrl(?string $certificateCrl)
    {
        $this->certificateCrl = $certificateCrl;
    }

    public function getCertificateCrlUrl(): ?string
    {
        return $this->certificateCrlUrl;
    }

    public function setCertificateCrlUrl(?string $certificateCrlUrl)
    {
        $this->certificateCrlUrl = $certificateCrlUrl;
    }

    public function getDeviceTypes(): Collection
    {
        return $this->deviceTypes;
    }

    public function setDeviceTypes(Collection $deviceTypes)
    {
        $this->deviceTypes = $deviceTypes;
    }
}
