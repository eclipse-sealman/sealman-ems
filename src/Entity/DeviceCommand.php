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
use App\Enum\DeviceCommandStatus;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Index(name: 'idx_createdAt', columns: ['created_at'])]
#[ORM\Index(name: 'idx_createdAt_id', columns: ['created_at', 'id'])]
class DeviceCommand implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface
{
    use DenyTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;

    #[Groups(['id', 'identification'])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Command name.
     */
    #[Groups(['deviceCommand:public'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $commandName = null;

    /**
     *  Command transaction ID.
     */
    #[Groups(['deviceCommand:public'])]
    #[ORM\Column(type: Types::STRING, unique: true)]
    private ?string $commandTransactionId = null;

    /**
     * Command status.
     */
    #[Groups(['deviceCommand:public'])]
    #[ORM\Column(type: Types::STRING, enumType: DeviceCommandStatus::class)]
    private ?DeviceCommandStatus $commandStatus = null;

    /**
     * Command status error category.
     */
    #[Groups(['deviceCommand:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $commandStatusErrorCategory = null;

    /**
     * Command status error PID.
     */
    #[Groups(['deviceCommand:public'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $commandStatusErrorPid = null;

    /**
     * Command status error message.
     */
    #[Groups(['deviceCommand:public'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commandStatusErrorMessage = null;

    /**
     * Command expire date.
     */
    #[Groups(['deviceCommand:public'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $expireAt = null;

    #[Groups(['deviceCommand:public'])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'commands')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Device $device = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getCommandName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getCommandName(): ?string
    {
        return $this->commandName;
    }

    public function setCommandName(?string $commandName)
    {
        $this->commandName = $commandName;
    }

    public function getCommandTransactionId(): ?string
    {
        return $this->commandTransactionId;
    }

    public function setCommandTransactionId(?string $commandTransactionId)
    {
        $this->commandTransactionId = $commandTransactionId;
    }

    public function getCommandStatus(): ?DeviceCommandStatus
    {
        return $this->commandStatus;
    }

    public function setCommandStatus(?DeviceCommandStatus $commandStatus)
    {
        $this->commandStatus = $commandStatus;
    }

    public function getCommandStatusErrorCategory(): ?string
    {
        return $this->commandStatusErrorCategory;
    }

    public function setCommandStatusErrorCategory(?string $commandStatusErrorCategory)
    {
        $this->commandStatusErrorCategory = $commandStatusErrorCategory;
    }

    public function getCommandStatusErrorPid(): ?string
    {
        return $this->commandStatusErrorPid;
    }

    public function setCommandStatusErrorPid(?string $commandStatusErrorPid)
    {
        $this->commandStatusErrorPid = $commandStatusErrorPid;
    }

    public function getCommandStatusErrorMessage(): ?string
    {
        return $this->commandStatusErrorMessage;
    }

    public function setCommandStatusErrorMessage(?string $commandStatusErrorMessage)
    {
        $this->commandStatusErrorMessage = $commandStatusErrorMessage;
    }

    public function getExpireAt(): ?\DateTime
    {
        return $this->expireAt;
    }

    public function setExpireAt(?\DateTime $expireAt)
    {
        $this->expireAt = $expireAt;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }
}
