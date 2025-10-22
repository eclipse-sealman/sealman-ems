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

/**
 * Developer friendly exception for unsupported values.
 */
class UnsupportedValueException extends \Exception
{
    public function __construct(mixed $value, string $message = 'Value "%s" (%s) is not supported', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf($message, $this->getValueAsString($value), $this->getValueType($value)), $code, $previous);
    }

    private function getValueAsString(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        $type = \gettype($value);
        switch ($type) {
            case 'boolean':
            case 'integer':
            case 'double':
            case 'string':
                return (string) $value;
        }

        if ('object' === $type && \method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '[complex value]';
    }

    private function getValueType(mixed $value): string
    {
        return \get_debug_type($value);
    }
}
