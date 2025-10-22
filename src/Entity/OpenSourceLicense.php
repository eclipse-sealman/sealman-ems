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

use App\Entity\Traits\CreatedAtEntityInterface;
use App\Entity\Traits\CreatedAtEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class OpenSourceLicense implements CreatedAtEntityInterface
{
    use CreatedAtEntityTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Package name.
     */
    #[Groups(['openSourceLicense:public'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Package version.
     */
    #[Groups(['openSourceLicense:public'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $version = null;

    /**
     * Package license type.
     */
    #[Groups(['openSourceLicense:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $licenseType = null;

    /**
     * Package description.
     */
    #[Groups(['openSourceLicense:public'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Package license content.
     */
    #[Groups(['openSourceLicense:public'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $licenseContent = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version)
    {
        $this->version = $version;
    }

    public function getLicenseType(): ?string
    {
        return $this->licenseType;
    }

    public function setLicenseType(?string $licenseType)
    {
        $this->licenseType = $licenseType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getLicenseContent(): ?string
    {
        return $this->licenseContent;
    }

    public function setLicenseContent(?string $licenseContent)
    {
        $this->licenseContent = $licenseContent;
    }
}
