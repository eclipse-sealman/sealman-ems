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
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Enum\ImportFileStatus;
use App\Model\AuditableInterface;
use App\Model\UploadInterface;
use App\Validator\Constraints\TusFile;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class ImportFile implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, UploadInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Filename.
     */
    #[Groups(['importFile:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $filename = null;

    /**
     * Filename.
     */
    #[Groups(['importFile:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['importFile:create'])]
    #[TusFile(simpleMimeTypes: 'excel', groups: ['importFile:create'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $filepath = null;

    /**
     * Should variables be applied when applying a template?
     */
    #[Groups(['importFile:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $applyVariables = false;

    /**
     * Should access tags be applied when applying a template?
     */
    #[Groups(['importFile:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $applyAccessTags = false;

    #[Groups(['importFile:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: ImportFileStatus::class)]
    private ?ImportFileStatus $status = null;

    /**
     * Connected import file rows.
     */
    #[ORM\OneToMany(mappedBy: 'importFile', targetEntity: ImportFileRow::class)]
    private Collection $rows;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getFilename();
    }

    public function getUploadFields(): array
    {
        return [
            'filepath',
        ];
    }

    public function getUploadDir(string $field): ?string
    {
        return '../private/import_file/'.$this->getId().'/';
    }

    public function __construct()
    {
        $this->rows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename)
    {
        $this->filename = $filename;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function setFilepath(?string $filepath)
    {
        $this->filepath = $filepath;
    }

    public function getRows(): Collection
    {
        return $this->rows;
    }

    public function setRows(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function getStatus(): ?ImportFileStatus
    {
        return $this->status;
    }

    public function setStatus(?ImportFileStatus $status)
    {
        $this->status = $status;
    }

    public function getApplyVariables(): ?bool
    {
        return $this->applyVariables;
    }

    public function setApplyVariables(?bool $applyVariables)
    {
        $this->applyVariables = $applyVariables;
    }

    public function getApplyAccessTags(): ?bool
    {
        return $this->applyAccessTags;
    }

    public function setApplyAccessTags(?bool $applyAccessTags)
    {
        $this->applyAccessTags = $applyAccessTags;
    }
}
