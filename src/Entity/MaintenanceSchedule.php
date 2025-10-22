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
use App\Model\AuditableInterface;
use App\Validator\Constraints\MaintenanceSchedule as MaintenanceScheduleValidator;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[MaintenanceScheduleValidator(groups: ['maintenanceSchedule:public'])]
class MaintenanceSchedule implements TimestampableEntityInterface, AuditableInterface
{
    use TimestampableEntityTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Name of schedule.
     */
    #[Groups(['maintenanceSchedule:public', 'maintenance:public', 'maintenanceLog:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['maintenanceSchedule:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $name = null;

    /**
     * Password to encrypt backup (optional).
     */
    #[Groups([AuditableInterface::ENCRYPTED_GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $backupPassword = null;

    /**
     * Should database be included in backup?
     */
    #[Groups(['maintenanceSchedule:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $backupDatabase = false;

    /**
     * Should filestorage be included in backup?
     */
    #[Groups(['maintenanceSchedule:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $backupFilestorage = false;

    /**
     * Day of month as a number. 1-31 or -1 which is interpreted as any).
     */
    #[Groups(['maintenanceSchedule:public', AuditableInterface::GROUP])]
    #[Assert\Range(min: -1, max: 31, groups: ['maintenanceSchedule:public'])]
    #[Assert\NotBlank(groups: ['maintenanceSchedule:public'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $dayOfMonth = -1;

    /**
     * Day of week as a number. 0-7 or -1 which is interpreted as any (0 or 7 is Sunday, 1 is Monday, and so on).
     */
    #[Groups(['maintenanceSchedule:public', AuditableInterface::GROUP])]
    #[Assert\Range(min: -1, max: 7, groups: ['maintenanceSchedule:public'])]
    #[Assert\NotBlank(groups: ['maintenanceSchedule:public'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $dayOfWeek = -1;

    /**
     * Hour. 0-23 or -1 which is interpreted as any.
     */
    #[Groups(['maintenanceSchedule:public', AuditableInterface::GROUP])]
    #[Assert\Range(min: -1, max: 23, groups: ['maintenanceSchedule:public'])]
    #[Assert\NotBlank(groups: ['maintenanceSchedule:public'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $hour = -1;

    /**
     * Minute. 0-59 or -1 which is interpreted as any.
     */
    #[Groups(['maintenanceSchedule:public', AuditableInterface::GROUP])]
    #[Assert\Range(min: -1, max: 59, groups: ['maintenanceSchedule:public'])]
    #[Assert\NotBlank(groups: ['maintenanceSchedule:public'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $minute = -1;

    /**
     * Next job should be created after this date.
     */
    #[Groups(['maintenanceSchedule:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $nextJobAt = null;

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

    public function getDayOfMonth(): ?int
    {
        return $this->dayOfMonth;
    }

    public function setDayOfMonth(?int $dayOfMonth)
    {
        $this->dayOfMonth = $dayOfMonth;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(?int $dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;
    }

    public function getHour(): ?int
    {
        return $this->hour;
    }

    public function setHour(?int $hour)
    {
        $this->hour = $hour;
    }

    public function getMinute(): ?int
    {
        return $this->minute;
    }

    public function setMinute(?int $minute)
    {
        $this->minute = $minute;
    }

    public function getNextJobAt(): ?\DateTime
    {
        return $this->nextJobAt;
    }

    public function setNextJobAt(?\DateTime $nextJobAt)
    {
        $this->nextJobAt = $nextJobAt;
    }
}
