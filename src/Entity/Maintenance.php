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
use App\Enum\MaintenanceStatus;
use App\Enum\MaintenanceType;
use App\Model\AuditableInterface;
use App\Model\UploadInterface;
use App\Service\MaintenanceManager;
use App\Validator\Constraints\Maintenance as MaintenanceValidator;
use App\Validator\Constraints\TusFile;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity]
#[MaintenanceValidator(groups: ['maintenance:public'])]
class Maintenance implements TimestampableEntityInterface, UploadInterface, DenyInterface, AuditableInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Type of maintenance job.
     */
    #[Groups(['maintenance:public', 'maintenanceLog:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['maintenance:public'])]
    #[ORM\Column(type: Types::STRING, enumType: MaintenanceType::class)]
    private ?MaintenanceType $type = null;

    /**
     * Backup or restore filepath.
     */
    #[Groups(['maintenance:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['maintenance:upload'])]
    #[TusFile(mimeTypes: 'application/zip', groups: ['maintenance:upload'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $filepath = null;

    /**
     * Password to encrypt backup (optional).
     */
    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $backupPassword = null;

    /**
     * Should database be included in backup?
     */
    #[Groups(['maintenance:public', 'maintenanceLog:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $backupDatabase = false;

    /**
     * Should filestorage be included in backup?
     */
    #[Groups(['maintenance:public', 'maintenanceLog:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $backupFilestorage = false;

    /**
     * Password to decrypt backup (optional).
     */
    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $restorePassword = null;

    /**
     * Should database be restored?
     */
    #[Groups(['maintenance:public', 'maintenanceLog:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $restoreDatabase = false;

    /**
     * Should filestorage be restored?
     */
    #[Groups(['maintenance:public', 'maintenanceLog:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $restoreFilestorage = false;

    /**
     * Status of maintenance job.
     */
    #[Groups(['maintenance:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, enumType: MaintenanceStatus::class)]
    private ?MaintenanceStatus $status = MaintenanceStatus::PENDING;

    /**
     * Is this a scheduled backup?
     */
    #[Groups(['maintenance:public', 'maintenanceLog:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $scheduledBackup = false;

    /**
     * Maintenance schedule.
     */
    #[Groups(['maintenance:public', 'maintenanceLog:public', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: MaintenanceSchedule::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MaintenanceSchedule $maintenanceSchedule = null;

    /**
     * Maintenance job logs.
     */
    #[ORM\OneToMany(mappedBy: 'maintenance', targetEntity: MaintenanceLog::class)]
    private Collection $logs;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) 'Maintenance job';
    }

    #[Groups(['maintenance:public', 'maintenanceLog:public'])]
    #[SerializedName('hasPassword')]
    public function hasPassword(): bool
    {
        return $this->getBackupPassword() || $this->getRestorePassword();
    }

    #[Groups(['maintenance:public'])]
    #[SerializedName('downloadUrl')]
    public function getDownloadUrl(): ?string
    {
        if (!$this->getFilepath()) {
            return null;
        }

        if (MaintenanceType::BACKUP_FOR_UPDATE === $this->getType()) {
            return '/web/api/download/maintenance/'.$this->getFilepath();
        }

        if (MaintenanceType::BACKUP === $this->getType()) {
            return '/web/api/download/maintenance/backup/'.$this->getFilepath();
        }

        return null;
    }

    public function getUploadDir(string $field): ?string
    {
        return MaintenanceManager::BACKUP_DIRECTORY.'/';
    }

    public function getUploadFields(): array
    {
        return [
            'filepath',
        ];
    }

    public function __construct()
    {
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

    public function getType(): ?MaintenanceType
    {
        return $this->type;
    }

    public function setType(?MaintenanceType $type)
    {
        $this->type = $type;
    }

    public function getBackupPassword(): ?string
    {
        return $this->backupPassword;
    }

    public function setBackupPassword(?string $backupPassword)
    {
        $this->backupPassword = $backupPassword;
    }

    public function getBackupDatabase(): ?bool
    {
        return $this->backupDatabase;
    }

    public function setBackupDatabase(?bool $backupDatabase)
    {
        $this->backupDatabase = $backupDatabase;
    }

    public function getBackupFilestorage(): ?bool
    {
        return $this->backupFilestorage;
    }

    public function setBackupFilestorage(?bool $backupFilestorage)
    {
        $this->backupFilestorage = $backupFilestorage;
    }

    public function getRestorePassword(): ?string
    {
        return $this->restorePassword;
    }

    public function setRestorePassword(?string $restorePassword)
    {
        $this->restorePassword = $restorePassword;
    }

    public function getRestoreDatabase(): ?bool
    {
        return $this->restoreDatabase;
    }

    public function setRestoreDatabase(?bool $restoreDatabase)
    {
        $this->restoreDatabase = $restoreDatabase;
    }

    public function getRestoreFilestorage(): ?bool
    {
        return $this->restoreFilestorage;
    }

    public function setRestoreFilestorage(?bool $restoreFilestorage)
    {
        $this->restoreFilestorage = $restoreFilestorage;
    }

    public function getStatus(): ?MaintenanceStatus
    {
        return $this->status;
    }

    public function setStatus(?MaintenanceStatus $status)
    {
        $this->status = $status;
    }

    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function setLogs(Collection $logs)
    {
        $this->logs = $logs;
    }

    public function getScheduledBackup(): ?bool
    {
        return $this->scheduledBackup;
    }

    public function setScheduledBackup(?bool $scheduledBackup)
    {
        $this->scheduledBackup = $scheduledBackup;
    }

    public function getMaintenanceSchedule(): ?MaintenanceSchedule
    {
        return $this->maintenanceSchedule;
    }

    public function setMaintenanceSchedule(?MaintenanceSchedule $maintenanceSchedule)
    {
        $this->maintenanceSchedule = $maintenanceSchedule;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function setFilepath(?string $filepath)
    {
        $this->filepath = $filepath;
    }
}
