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

class LogsCollection extends \IteratorIterator
{
    public function __construct(LogModel ...$logModels)
    {
        parent::__construct(new \ArrayIterator($logModels));
    }

    public function current(): LogModel
    {
        return parent::current();
    }

    public function add(LogModel $logModel): void
    {
        $this->getInnerIterator()->append($logModel);
    }

    public function count(): int
    {
        return $this->getInnerIterator()->count();
    }

    public function merge(LogsCollection $logsCollection): void
    {
        foreach ($logsCollection as $logModel) {
            $this->add($logModel);
        }
    }

    public function getLogLevelCollection(LogLevel ...$logLevels): LogsCollection
    {
        $resultLogsCollection = new LogsCollection();

        foreach ($this as $logModel) {
            if (in_array($logModel->getLogLevel(), $logLevels)) {
                $resultLogsCollection->add($logModel);
            }
        }

        return $resultLogsCollection;
    }
}
