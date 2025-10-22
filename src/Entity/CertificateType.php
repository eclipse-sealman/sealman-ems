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
use App\Enum\CertificateBehavior;
use App\Enum\CertificateCategory;
use App\Enum\CertificateEntity;
use App\Enum\PkiHashAlgorithm;
use App\Enum\PkiKeyLength;
use App\Enum\PkiType;
use App\Model\AuditableInterface;
use App\Repository\CertificateTypeRepository;
use App\Validator\Constraints\CertificateType as CertificateTypeValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Carve\ApiBundle\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CertificateTypeRepository::class)]
#[UniqueEntity(fields: ['name'], groups: ['certificateType:common'])]
#[UniqueEntity(fields: ['commonNamePrefix'], groups: ['certificateType:common'])]
#[UniqueEntity(fields: ['variablePrefix'], groups: ['certificateType:common'])]
#[CertificateTypeValidator(groups: ['certificateType:common'])]
class CertificateType implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Certificate category.
     */
    #[Groups(['certificateType:public', 'identification', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: CertificateCategory::class)]
    private ?CertificateCategory $certificateCategory = null;

    /**
     * Certificate entity (Specifies entity that can use this certificate type: User or Device).
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['certificateType:create'])]
    #[ORM\Column(type: Types::STRING, enumType: CertificateEntity::class)]
    private ?CertificateEntity $certificateEntity = null;

    /**
     * Certificate type name.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Certificate type common name prefix.
     */
    #[Assert\Length(max: 3, groups: ['certificateType:common'])]
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    // Not blank handled by CertificateType validator - due to predefined certificateTypes requirements
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $commonNamePrefix = null;

    /**
     * Certificate type variable prefix.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    // Not blank handled by CertificateType validator - due to predefined certificateTypes requirements
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $variablePrefix = null;

    /**
     * Is certificate type enabled?
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enabled = false;

    /**
     * Can certificate be uploaded by user?
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $uploadEnabled = false;

    /**
     * Can certificate be downloaded by user?
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $downloadEnabled = false;

    /**
     * Can uploaded certificate be deleted by user?
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $deleteEnabled = false;

    /**
     * Can certificate be generated and revoked using PKI by user?
     */
    #[Groups(['certificateType:public', 'identification', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $pkiEnabled = false;

    /**
     * Automatic enabled behaviour.
     */
    #[Groups(['certificateType:public', 'identification', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: CertificateBehavior::class)]
    private ?CertificateBehavior $enabledBehaviour = CertificateBehavior::NONE;

    /**
     * Automatic disabled behaviour.
     */
    #[Groups(['certificateType:public', 'identification', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: CertificateBehavior::class)]
    private ?CertificateBehavior $disabledBehaviour = CertificateBehavior::NONE;

    /**
     * Certificate PKI type (none, SCEP).
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::STRING, enumType: PkiType::class)]
    private ?PkiType $pkiType = PkiType::NONE;

    /**
     * Should SCEP server SSL certificate be verified.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $scepVerifyServerSslCertificate = false;

    /**
     * SCEP url.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[Assert\Url(groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $scepUrl = null;

    /**
     * SCEP CRL url.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[Assert\Url(groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $scepCrlUrl = null;

    /**
     * SCEP revocation url.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[Assert\Url(groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $scepRevocationUrl = null;

    /**
     * SCEP timeout in seconds.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[Assert\GreaterThanOrEqual(value: 1, groups: ['certificateType:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $scepTimeout = 5;

    /**
     * SCEP revocation basic auth user.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $scepRevocationBasicAuthUser = null;

    /**
     * SCEP revocation basic auth password.
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $scepRevocationBasicAuthPassword = null;

    /**
     * SCEP hash function ("SHA256", "SHA384", "SHA512").
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: PkiHashAlgorithm::class)]
    private ?PkiHashAlgorithm $scepHashFunction = PkiHashAlgorithm::SHA512;

    /**
     * SCEP hash function ("2048", "4096").
     */
    #[Groups(['certificateType:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: PkiKeyLength::class)]
    private ?PkiKeyLength $scepKeyLength = PkiKeyLength::KEY4096;

    #[ORM\OneToMany(mappedBy: 'certificateType', targetEntity: DeviceTypeCertificateType::class)]
    private Collection $deviceTypeCertificateTypes;

    #[ORM\OneToMany(mappedBy: 'certificateType', targetEntity: Certificate::class)]
    private Collection $certificates;

    #[ORM\OneToMany(mappedBy: 'deviceTypeCertificateTypeCredential', targetEntity: DeviceType::class)]
    private Collection $deviceTypeCertificateTypeCredentials;

    /**
     * Helper field used to provide information if certificate type enabled and available (depending on license and system state).
     */
    #[Groups(['certificateType:public'])]
    private ?bool $isAvailable = false;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function __construct()
    {
        $this->deviceTypeCertificateTypes = new ArrayCollection();
        $this->certificates = new ArrayCollection();
        $this->deviceTypeCertificateTypeCredentials = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getCertificateCategory(): ?CertificateCategory
    {
        return $this->certificateCategory;
    }

    public function setCertificateCategory(?CertificateCategory $certificateCategory)
    {
        $this->certificateCategory = $certificateCategory;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function setCommonNamePrefix(?string $commonNamePrefix)
    {
        $this->commonNamePrefix = $commonNamePrefix;
    }

    public function setVariablePrefix(?string $variablePrefix)
    {
        $this->variablePrefix = $variablePrefix;
    }

    public function getUploadEnabled(): ?bool
    {
        return $this->uploadEnabled;
    }

    public function setUploadEnabled(?bool $uploadEnabled)
    {
        $this->uploadEnabled = $uploadEnabled;
    }

    public function getDownloadEnabled(): ?bool
    {
        return $this->downloadEnabled;
    }

    public function setDownloadEnabled(?bool $downloadEnabled)
    {
        $this->downloadEnabled = $downloadEnabled;
    }

    public function getDeleteEnabled(): ?bool
    {
        return $this->deleteEnabled;
    }

    public function setDeleteEnabled(?bool $deleteEnabled)
    {
        $this->deleteEnabled = $deleteEnabled;
    }

    public function getPkiEnabled(): ?bool
    {
        return $this->pkiEnabled;
    }

    public function setPkiEnabled(?bool $pkiEnabled)
    {
        $this->pkiEnabled = $pkiEnabled;
    }

    public function getEnabledBehaviour(): ?CertificateBehavior
    {
        return $this->enabledBehaviour;
    }

    public function setEnabledBehaviour(?CertificateBehavior $enabledBehaviour)
    {
        $this->enabledBehaviour = $enabledBehaviour;
    }

    public function getDisabledBehaviour(): ?CertificateBehavior
    {
        return $this->disabledBehaviour;
    }

    public function setDisabledBehaviour(?CertificateBehavior $disabledBehaviour)
    {
        $this->disabledBehaviour = $disabledBehaviour;
    }

    public function getPkiType(): ?PkiType
    {
        return $this->pkiType;
    }

    public function setPkiType(?PkiType $pkiType)
    {
        $this->pkiType = $pkiType;
    }

    public function getScepUrl(): ?string
    {
        return $this->scepUrl;
    }

    public function setScepUrl(?string $scepUrl)
    {
        $this->scepUrl = $scepUrl;
    }

    public function getScepCrlUrl(): ?string
    {
        return $this->scepCrlUrl;
    }

    public function setScepCrlUrl(?string $scepCrlUrl)
    {
        $this->scepCrlUrl = $scepCrlUrl;
    }

    public function getScepRevocationUrl(): ?string
    {
        return $this->scepRevocationUrl;
    }

    public function setScepRevocationUrl(?string $scepRevocationUrl)
    {
        $this->scepRevocationUrl = $scepRevocationUrl;
    }

    public function getScepRevocationBasicAuthUser(): ?string
    {
        return $this->scepRevocationBasicAuthUser;
    }

    public function setScepRevocationBasicAuthUser(?string $scepRevocationBasicAuthUser)
    {
        $this->scepRevocationBasicAuthUser = $scepRevocationBasicAuthUser;
    }

    public function getScepRevocationBasicAuthPassword(): ?string
    {
        return $this->scepRevocationBasicAuthPassword;
    }

    public function setScepRevocationBasicAuthPassword(?string $scepRevocationBasicAuthPassword)
    {
        $this->scepRevocationBasicAuthPassword = $scepRevocationBasicAuthPassword;
    }

    public function getScepHashFunction(): ?PkiHashAlgorithm
    {
        return $this->scepHashFunction;
    }

    public function setScepHashFunction(?PkiHashAlgorithm $scepHashFunction)
    {
        $this->scepHashFunction = $scepHashFunction;
    }

    public function getScepKeyLength(): ?PkiKeyLength
    {
        return $this->scepKeyLength;
    }

    public function setScepKeyLength(?PkiKeyLength $scepKeyLength)
    {
        $this->scepKeyLength = $scepKeyLength;
    }

    public function getDeviceTypeCertificateTypes(): Collection
    {
        return $this->deviceTypeCertificateTypes;
    }

    public function setDeviceTypeCertificateTypes(Collection $deviceTypeCertificateTypes)
    {
        $this->deviceTypeCertificateTypes = $deviceTypeCertificateTypes;
    }

    public function getCertificates(): Collection
    {
        return $this->certificates;
    }

    public function setCertificates(Collection $certificates)
    {
        $this->certificates = $certificates;
    }

    public function getScepVerifyServerSslCertificate(): ?bool
    {
        return $this->scepVerifyServerSslCertificate;
    }

    public function setScepVerifyServerSslCertificate(?bool $scepVerifyServerSslCertificate)
    {
        $this->scepVerifyServerSslCertificate = $scepVerifyServerSslCertificate;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function getIsAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(?bool $isAvailable)
    {
        $this->isAvailable = $isAvailable;
    }

    public function getCommonNamePrefix(): ?string
    {
        return $this->commonNamePrefix;
    }

    public function getVariablePrefix(): ?string
    {
        return $this->variablePrefix;
    }

    public function getCertificateEntity(): ?CertificateEntity
    {
        return $this->certificateEntity;
    }

    public function setCertificateEntity(?CertificateEntity $certificateEntity)
    {
        $this->certificateEntity = $certificateEntity;
    }

    public function getDeviceTypeCertificateTypeCredentials(): Collection
    {
        return $this->deviceTypeCertificateTypeCredentials;
    }

    public function setDeviceTypeCertificateTypeCredentials(Collection $deviceTypeCertificateTypeCredentials)
    {
        $this->deviceTypeCertificateTypeCredentials = $deviceTypeCertificateTypeCredentials;
    }

    public function getScepTimeout(): ?int
    {
        return $this->scepTimeout;
    }

    public function setScepTimeout(?int $scepTimeout)
    {
        $this->scepTimeout = $scepTimeout;
    }
}
