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

trait LogsCollectorTrait
{
    private ?LogsCollection $logsCollection = null;

    public function clearLogs(): void
    {
        $this->logsCollection = null;
    }

    public function hasLogs(): bool
    {
        return $this->getLogsCollection()->count() > 0;
    }

    public function getLogs(): LogsCollection
    {
        return $this->getLogsCollection();
    }

    public function addLogsCollection(LogsCollection $logsCollection): LogsCollection
    {
        $this->getLogsCollection()->merge($logsCollection);

        return $this->getLogs();
    }

    public function addLogModel(LogModel $logModel): LogModel
    {
        $this->getLogsCollection()->add($logModel);

        return $logModel;
    }

    public function addLog(LogLevel $logLevel, string $message, array $messageVariables = []): LogModel
    {
        $logModel = new LogModel($logLevel, $message, $messageVariables);

        return $this->addLogModel($logModel);
    }

    public function addLogDebug(string $message, array $messageVariables = []): LogModel
    {
        return $this->addLog(LogLevel::DEBUG, $message, $messageVariables);
    }

    public function addLogInfo(string $message, array $messageVariables = []): LogModel
    {
        return $this->addLog(LogLevel::INFO, $message, $messageVariables);
    }

    public function addLogWarning(string $message, array $messageVariables = []): LogModel
    {
        return $this->addLog(LogLevel::WARNING, $message, $messageVariables);
    }

    public function addLogError(string $message, array $messageVariables = []): LogModel
    {
        return $this->addLog(LogLevel::ERROR, $message, $messageVariables);
    }

    public function addLogCritical(string $message, array $messageVariables = []): LogModel
    {
        return $this->addLog(LogLevel::CRITICAL, $message, $messageVariables);
    }

    protected function getLogsCollection(): LogsCollection
    {
        if (null === $this->logsCollection) {
            $this->logsCollection = new LogsCollection();
        }

        return $this->logsCollection;
    }
}
