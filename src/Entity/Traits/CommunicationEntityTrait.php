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

namespace App\Entity\Traits;

use App\Model\AuditableInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait CommunicationEntityTrait
{
    #[Groups(['communication:admin', 'communication:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $xForwardedFor = null;

    #[Groups(['communication:admin', 'communication:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $host = null;

    #[Groups(['communication:admin', 'communication:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $ipv6Prefix = null;

    #[Groups(['communication:admin', 'communication:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $uptime = null;

    #[Groups(['communication:admin', 'communication:smartems', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $uptimeSeconds = null;

    #[Groups(['communication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $seenAt = null;

    /**
     * Serial number.
     */
    #[Groups(['communication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $serialNumber = null;

    /**
     * Model.
     */
    #[Groups(['communication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $model = null;

    /**
     * Registration ID.
     */
    #[Groups(['communication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $registrationId = null;

    /**
     * Endorsment key.
     */
    #[Groups(['communication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $endorsementKey = null;

    /**
     * Hardware version.
     */
    #[Groups(['communication:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $hardwareVersion = null;

    public function getXForwardedFor(): ?string
    {
        return $this->xForwardedFor;
    }

    public function setXForwardedFor(?string $xForwardedFor)
    {
        $this->xForwardedFor = $xForwardedFor;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host)
    {
        $this->host = $host;
    }

    public function getIpv6Prefix(): ?string
    {
        return $this->ipv6Prefix;
    }

    public function setIpv6Prefix(?string $ipv6Prefix)
    {
        $this->ipv6Prefix = $ipv6Prefix;
    }

    public function getUptime(): ?string
    {
        return $this->uptime;
    }

    public function setUptime(?string $uptime)
    {
        $this->uptime = $uptime;
    }

    public function getUptimeSeconds(): ?int
    {
        return $this->uptimeSeconds;
    }

    public function setUptimeSeconds(?int $uptimeSeconds)
    {
        $this->uptimeSeconds = $uptimeSeconds;
    }

    public function getSeenAt(): ?\DateTime
    {
        return $this->seenAt;
    }

    public function setSeenAt(?\DateTime $seenAt)
    {
        $this->seenAt = $seenAt;
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
}
