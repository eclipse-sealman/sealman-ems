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

namespace App\Service\Trait;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

// Trait that helps with running shell commands
trait RunCommandHelperTrait
{
    /**
     * $exceptionHandler(array $command, ProcessFailedException $exception).
     */
    protected function runCommand(array $command, int $timeout = 30, ?callable $exceptionHandler = null): ?string
    {
        $process = new Process($command);
        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            $exception = new ProcessFailedException($process);

            if (null !== $exceptionHandler) {
                $exceptionHandler($command, $exception);
            } else {
                throw $exception;
            }
        }

        return $process->getOutput();
    }
}
