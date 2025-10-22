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

namespace App\Model;

use App\Enum\LogLevel;
use Carve\ApiBundle\Validator\Constraints as Assert;

class DiagnoseLogModel
{
    /**
     * Log Level.
     */
    #[Assert\NotBlank(groups: ['vpnContainerClientLogs'])]
    private ?LogLevel $logLevel = null;

    /**
     *  Log message.
     */
    #[Assert\NotBlank(groups: ['vpnContainerClientLogs'])]
    #[Assert\Length(max: 255)]
    private ?string $message = null;

    /**
     *  Timestamp that log was generated.
     */
    #[Assert\NotBlank(groups: ['vpnContainerClientLogs'])]
    private ?\DateTime $logAt = null;

    public function getLogLevel(): ?LogLevel
    {
        return $this->logLevel;
    }

    public function setLogLevel(?LogLevel $logLevel)
    {
        $this->logLevel = $logLevel;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message)
    {
        $this->message = $message;
    }

    public function getLogAt(): ?\DateTime
    {
        return $this->logAt;
    }

    public function setLogAt(?\DateTime $logAt)
    {
        $this->logAt = $logAt;
    }
}
