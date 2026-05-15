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

namespace App\Tool;

use App\Enum\VariableType;
use App\Exception\UnsupportedValueException;

/**
 * TypeCaster utility class for type conversion and validation.
 *
 * Provides static methods for robust type casting and validation across the application,
 */
class TypeCaster
{
    /**
     * Converts and validates a mixed value to boolean.
     * handles on, off, etc. Details can be found in EnvProcessingTest.
     * Returns always boolean value (if convert fail $default is returned).
     */
    public static function toBool(mixed $value, bool $default = false): bool
    {
        $boolValue = TypeCaster::toBoolOrNull($value);
        if (null === $boolValue) {
            // Return default for null, empty string, empty array, etc.
            return $default;
        }

        return $boolValue;
    }

    /**
     * Converts and validates a mixed value to boolean.
     * handles on, off, etc.
     * Returns boolean value or if convert fail null is returned.
     */
    public static function toBoolOrNull(mixed $value): null|bool
    {
        if (is_null($value)) {
            return null;
        }

        // If already boolean, return as-is
        if (\is_bool($value)) {
            return $value;
        }

        // Handle numeric types (int, float)
        if (\is_int($value) || \is_float($value)) {
            return (bool) $value;
        }

        // Handle non-empty strings with filter_var for robust conversion
        if (\is_string($value)) {
            // Use chained filter_var with fallback logic
            // Try FILTER_VALIDATE_BOOLEAN (recognizes: 1, true, on, yes for true; 0, false, off, no for false)
            $value = trim($value);
            if (\strlen($value) <= 0) {
                return null;
            }

            return \filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
        }

        return null;
    }

    /**
     * Converts a mixed input value into a string representation for a given variable type.
     */
    public static function mixedToString(null|bool|int|string|float|array $inputValue): string
    {
        if (\is_null($inputValue)) {
            return 'null';
        }

        if (\is_array($inputValue)) {
            return \json_encode($inputValue);
        }

        if (\is_bool($inputValue)) {
            return $inputValue ? 'true' : 'false';
        }

        return \strval($inputValue);
    }

    /**
     * Parses a string value into the typed value for a given variable type.
     */
    public static function getTypedVariableTypeValue(VariableType $variableType, bool|int|string|float|array $inputValue): null|bool|int|float|string|array
    {
        switch ($variableType) {
            case VariableType::STRING:
                return $inputValue;
            case VariableType::BOOLEAN:
                return TypeCaster::toBoolOrNull($inputValue);
            case VariableType::INTEGER:
                return filter_var($inputValue, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            case VariableType::FLOAT:
                return filter_var($inputValue, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            case VariableType::JSON_OBJECT:
                return json_decode($inputValue, true);
            default:
                throw new UnsupportedValueException($variableType);
        }
    }
}
