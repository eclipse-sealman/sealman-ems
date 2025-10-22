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

use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Enum\ImportFileRowImportStatus;
use App\Enum\ImportFileRowParseStatus;
use App\Validator\Constraints\ImportFileRowReinstallConfig1;
use App\Validator\Constraints\ImportFileRowReinstallConfig2;
use App\Validator\Constraints\ImportFileRowReinstallConfig3;
use App\Validator\Constraints\ImportFileRowTemplate;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ImportFileRowTemplate(groups: ['importFileRow:template'])]
#[ImportFileRowReinstallConfig1(groups: ['importFileRow:reinstallConfig1'])]
#[ImportFileRowReinstallConfig2(groups: ['importFileRow:reinstallConfig2'])]
#[ImportFileRowReinstallConfig3(groups: ['importFileRow:reinstallConfig3'])]
class ImportFileRow implements DenyInterface, TimestampableEntityInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Excel row key (starting from 0).
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $rowKey = null;

    /**
     * Status of parsed row.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::STRING, enumType: ImportFileRowParseStatus::class)]
    private ?ImportFileRowParseStatus $parseStatus = null;

    /**
     * Status of imported row.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::STRING, enumType: ImportFileRowImportStatus::class, nullable: true)]
    private ?ImportFileRowImportStatus $importStatus = null;

    /**
     * Device type.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\ManyToOne(targetEntity: DeviceType::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?DeviceType $deviceType = null;

    /**
     * Template.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\ManyToOne(targetEntity: Template::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Template $template = null;

    /**
     * Access tags.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\ManyToMany(targetEntity: AccessTag::class)]
    private Collection $accessTags;

    /**
     * Labels.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\ManyToMany(targetEntity: Label::class)]
    private Collection $labels;

    /**
     * Variables.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\OneToMany(mappedBy: 'row', targetEntity: ImportFileRowVariable::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $variables;

    /**
     * Device name.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $name = null;

    /**
     * Serial number.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $serialNumber = null;

    /**
     * Model.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $model = null;

    /**
     * IMSI.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $imsi = null;

    /**
     * Registration ID.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $registrationId = null;

    /**
     * Endorsment key.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $endorsementKey = null;

    /**
     * Hardware version.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $hardwareVersion = null;

    /**
     * Should primary config be reinstalled?
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallConfig1 = false;

    /**
     * Should secondary config be reinstalled?
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallConfig2 = false;

    /**
     * Should tertiary config be reinstalled?
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $reinstallConfig3 = false;

    /**
     * Is device enabled?
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enabled = false;

    /**
     * Import file.
     */
    #[Groups(['importFileRow:public'])]
    #[ORM\ManyToOne(targetEntity: ImportFile::class, inversedBy: 'rows')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ImportFile $importFile = null;

    /**
     * Connected logs.
     */
    #[ORM\OneToMany(mappedBy: 'row', targetEntity: ImportFileRowLog::class)]
    private Collection $logs;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function addLabel(Label $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }
    }

    public function removeLabel(Label $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }
    }

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
        }
    }

    public function addVariable(ImportFileRowVariable $variable)
    {
        if (!$this->variables->contains($variable)) {
            $this->variables[] = $variable;
            $variable->setRow($this);
        }
    }

    public function removeVariable(ImportFileRowVariable $variable)
    {
        if ($this->variables->removeElement($variable)) {
            if ($variable->getRow() === $this) {
                $variable->setRow(null);
            }
        }
    }

    public function __construct()
    {
        $this->accessTags = new ArrayCollection();
        $this->labels = new ArrayCollection();
        $this->variables = new ArrayCollection();
        $this->logs = new ArrayCollection();
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

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getImportFile(): ?ImportFile
    {
        return $this->importFile;
    }

    public function setImportFile(?ImportFile $importFile)
    {
        $this->importFile = $importFile;
    }

    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function setLogs(Collection $logs)
    {
        $this->logs = $logs;
    }

    public function getRowKey(): ?int
    {
        return $this->rowKey;
    }

    public function setRowKey(?int $rowKey)
    {
        $this->rowKey = $rowKey;
    }

    public function getReinstallConfig1(): ?bool
    {
        return $this->reinstallConfig1;
    }

    public function setReinstallConfig1(?bool $reinstallConfig1)
    {
        $this->reinstallConfig1 = $reinstallConfig1;
    }

    public function getReinstallConfig2(): ?bool
    {
        return $this->reinstallConfig2;
    }

    public function setReinstallConfig2(?bool $reinstallConfig2)
    {
        $this->reinstallConfig2 = $reinstallConfig2;
    }

    public function getReinstallConfig3(): ?bool
    {
        return $this->reinstallConfig3;
    }

    public function setReinstallConfig3(?bool $reinstallConfig3)
    {
        $this->reinstallConfig3 = $reinstallConfig3;
    }

    public function getEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber)
    {
        $this->serialNumber = $serialNumber;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model)
    {
        $this->model = $model;
    }

    public function getImsi(): ?string
    {
        return $this->imsi;
    }

    public function setImsi(?string $imsi)
    {
        $this->imsi = $imsi;
    }

    public function getRegistrationId(): ?string
    {
        return $this->registrationId;
    }

    public function setRegistrationId(?string $registrationId)
    {
        $this->registrationId = $registrationId;
    }

    public function getEndorsementKey(): ?string
    {
        return $this->endorsementKey;
    }

    public function setEndorsementKey(?string $endorsementKey)
    {
        $this->endorsementKey = $endorsementKey;
    }

    public function getHardwareVersion(): ?string
    {
        return $this->hardwareVersion;
    }

    public function setHardwareVersion(?string $hardwareVersion)
    {
        $this->hardwareVersion = $hardwareVersion;
    }

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template)
    {
        $this->template = $template;
    }

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getVariables(): Collection
    {
        return $this->variables;
    }

    public function setVariables(Collection $variables)
    {
        $this->variables = $variables;
    }

    public function getParseStatus(): ?ImportFileRowParseStatus
    {
        return $this->parseStatus;
    }

    public function setParseStatus(?ImportFileRowParseStatus $parseStatus)
    {
        $this->parseStatus = $parseStatus;
    }

    public function getImportStatus(): ?ImportFileRowImportStatus
    {
        return $this->importStatus;
    }

    public function setImportStatus(?ImportFileRowImportStatus $importStatus)
    {
        $this->importStatus = $importStatus;
    }

    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function setLabels(Collection $labels)
    {
        $this->labels = $labels;
    }
}
