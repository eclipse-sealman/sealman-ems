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

namespace App\HttpClient;

class HttpClientException extends \Exception
{
    protected string $logMessage;
    protected ?array $logMessageVariables = [];

    public function __construct(string $logMessage, ?array $logMessageVariables = [], ?string $message = null, int $code = 0, \Throwable $previous = null)
    {
        $this->logMessage = $logMessage;
        $this->logMessageVariables = $logMessageVariables;

        parent::__construct(null === $message ? $logMessage : $message, $code, $previous);
    }

    public function getLogMessage(): string
    {
        return $this->logMessage;
    }

    public function setLogMessage(string $logMessage)
    {
        $this->logMessage = $logMessage;
    }

    public function getLogMessageVariables(): ?array
    {
        return $this->logMessageVariables;
    }

    public function setLogMessageVariables(?array $logMessageVariables)
    {
        $this->logMessageVariables = $logMessageVariables;
    }
}
