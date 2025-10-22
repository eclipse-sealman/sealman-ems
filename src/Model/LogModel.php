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

/**
 * Contains log message.
 */
class LogModel
{
    /**
     * Log message translation key.
     */
    private string $message;

    /**
     * Log message translation variables (plain not eveloped in {{ }}).
     */
    private array $messageVariables = [];

    private LogLevel $logLevel;

    private \DateTime $createdAt;

    public function __construct(LogLevel $logLevel, string $message, array $messageVariables = [], ?\DateTime $createdAt = new \DateTime())
    {
        $this->logLevel = $logLevel;
        $this->message = $message;
        $this->messageVariables = $messageVariables;
        $this->createdAt = $createdAt;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getMessageVariables(): array
    {
        return $this->messageVariables;
    }

    public function setMessageVariables(array $messageVariables)
    {
        $this->messageVariables = $messageVariables;
    }

    public function getLogLevel(): LogLevel
    {
        return $this->logLevel;
    }

    public function setLogLevel(LogLevel $logLevel)
    {
        $this->logLevel = $logLevel;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }
}
