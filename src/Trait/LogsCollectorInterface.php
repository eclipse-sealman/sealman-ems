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

namespace App\Trait;

use App\Enum\LogLevel;
use App\Model\LogModel;
use App\Model\LogsCollection;

interface LogsCollectorInterface
{
    public function clearLogs(): void;

    public function hasLogs(): bool;

    public function getLogs(): LogsCollection;

    public function addLogsCollection(LogsCollection $logsCollection): LogsCollection;

    public function addLogModel(LogModel $logModel): LogModel;

    public function addLog(LogLevel $logLevel, string $message, array $messageVariables = []): LogModel;

    public function addLogDebug(string $message, array $messageVariables = []): LogModel;

    public function addLogInfo(string $message, array $messageVariables = []): LogModel;

    public function addLogWarning(string $message, array $messageVariables = []): LogModel;

    public function addLogError(string $message, array $messageVariables = []): LogModel;

    public function addLogCritical(string $message, array $messageVariables = []): LogModel;
}
