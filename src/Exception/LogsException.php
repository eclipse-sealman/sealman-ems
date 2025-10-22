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

namespace App\Exception;

use App\Enum\LogLevel;
use App\Model\LogModel;
use App\Model\LogsCollection;
use App\Trait\LogsCollectorInterface;
use Carve\ApiBundle\Enum\RequestExecutionExceptionSeverity;
use Carve\ApiBundle\Exception\RequestExecutionException;

class LogsException extends RequestExecutionException
{
    public function __construct(LogModel|LogsCollection|LogsCollectorInterface|null $log = null, ?string $message = '', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(null, [], null, $message, $previous, $code, $headers);

        switch (true) {
            case $log instanceof LogModel:
                $this->addLogModel($log);
                break;
            case $log instanceof LogsCollection:
                $this->addLogsCollection($log);
                break;
            case $log instanceof LogsCollectorInterface:
                $this->addLogsCollector($log);
                break;
        }
    }

    public function addLogsCollector(LogsCollectorInterface $logsCollector): void
    {
        $this->addLogsCollection($logsCollector->getLogs());
    }

    public function addLogsCollection(LogsCollection $logsCollection): void
    {
        foreach ($logsCollection as $logModel) {
            $this->addLogModel($logModel);
        }
    }

    public function addLogModel(LogModel $logModel): void
    {
        $severity = $this->getLogModelSeverity($logModel);
        $this->add($logModel->getMessage(), $logModel->getMessageVariables(), $severity);
    }

    protected function getLogModelSeverity(LogModel $logModel): RequestExecutionExceptionSeverity
    {
        switch ($logModel->getLogLevel()) {
            case LogLevel::DEBUG:
            case LogLevel::INFO:
            case LogLevel::WARNING:
                return RequestExecutionExceptionSeverity::WARNING;
            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
                return RequestExecutionExceptionSeverity::ERROR;
        }

        throw new \Exception('Unsupported logLevel "'.$logModel->getLogLevel()->value.'"');
    }
}
