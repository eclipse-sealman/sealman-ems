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

use App\Entity\Traits\BlameableEntityTrait;
use App\Entity\Traits\BlameableEntityInterface;
use App\Entity\Traits\CertificateDataEntityTrait;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Enum\CertificateCategory;
use App\Model\AuditableInterface;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class Certificate implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface
{
    use DenyTrait;
    use CertificateDataEntityTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Certificate type.
     */
    #[Groups(['certificate:admin', 'certificate:vpn', 'certificate:smartems', 'certificate:private', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: CertificateType::class, inversedBy: 'certificates')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CertificateType $certificateType = null;

    /**
     * Device.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'certificates')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Device $device = null;

    /**
     * User.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'certificates')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Should certificate be revoked on next communication.
     */
    #[Groups([AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private ?bool $revokeCertificateOnNextCommunication = false;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getCertificateType()?->getName() ?: 'certificate';
    }

    public function getTarget(): User|Device|null
    {
        if ($this->getUser()) {
            return $this->getUser();
        }
        if ($this->getDevice()) {
            return $this->getDevice();
        }

        return null;
    }

    public function getCertificateCategory(): ?CertificateCategory
    {
        return $this->getCertificateType()?->getCertificateCategory() ?: null;
    }

    public function __construct()
    {
    }

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

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
    }

    public function getRevokeCertificateOnNextCommunication(): ?bool
    {
        return $this->revokeCertificateOnNextCommunication;
    }

    public function setRevokeCertificateOnNextCommunication(?bool $revokeCertificateOnNextCommunication)
    {
        $this->revokeCertificateOnNextCommunication = $revokeCertificateOnNextCommunication;
    }
}
