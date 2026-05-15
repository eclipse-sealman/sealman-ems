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

namespace App\Service;

use App\Enum\VariableType;
use App\Exception\UnsupportedValueException;
use App\Tool\TypeCaster;

class VariableManager
{
    /**
     * Method returns typed variable value based on variable type. When casting fails, it returns null.
     */
    public function getTypedVariableValue(VariableType $variableType, string $variableValue): null|bool|int|float|string|array
    {
        return TypeCaster::getTypedVariableTypeValue($variableType, $variableValue);
    }

    /**
     * Method returns normalized variable value based on variable type. When casting fails, it returns null.
     */
    public function getNormalizedVariableValue(VariableType $variableType, string $variableValue): null|string
    {
        $isValid = $this->isValidType($variableType, $variableValue);
        if (!$isValid) {
            return null;
        }

        $typedValue = $this->getTypedVariableValue($variableType, $variableValue);

        switch ($variableType) {
            case VariableType::STRING:
            case VariableType::INTEGER:
            case VariableType::FLOAT:
                return (string) $typedValue;
            case VariableType::BOOLEAN:
                if (true === $typedValue) {
                    return 'true';
                }
                if (false === $typedValue) {
                    return 'false';
                }

                return null;
            case VariableType::JSON_OBJECT:
                return \json_encode($typedValue);
            default:
                throw new UnsupportedValueException($variableType);
        }
    }

    public function isValidType(VariableType $variableType, string $variableValue): bool
    {
        $typedValue = $this->getTypedVariableValue($variableType, $variableValue);
        if (null === $typedValue) {
            return false;
        }

        // Cover edge cases (i.e. with \json_decode parsing a string 'true' as bool value true)
        switch ($variableType) {
            case VariableType::BOOLEAN:
                return is_bool($typedValue);
            case VariableType::INTEGER:
                return is_int($typedValue);
            case VariableType::FLOAT:
                return is_float($typedValue);
            case VariableType::JSON_OBJECT:
                return is_array($typedValue);
            case VariableType::STRING:
                return is_string($typedValue);
            default:
                throw new UnsupportedValueException($variableType);
        }

        return true;
    }
}
