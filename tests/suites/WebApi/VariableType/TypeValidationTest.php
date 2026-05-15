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

namespace Tests\Suites\WebApi\VariableType;

use App\Enum\VariableType;
use App\Service\VariableManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Tests different values (valid and invalid) against variable types.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class TypeValidationTest extends AbstractTestCase
{
    public static function getRow(
        VariableType $type,
        /*
         * Keys are tested string values, values are the expected typed value (use null for invalid values)
         */
        array $values,
    ): array {
        return [$type, $values];
    }

    public static function getData(): array
    {
        $data = [];

        $data[] = self::getRow(
            type: VariableType::BOOLEAN,
            values: [
                // True
                'true' => true,
                // False
                'false' => false,
                'on' => true,
                'yes' => true,
                '1' => true,
                'off' => false,
                'no' => false,
                '0' => false,
                // Invalid
                '' => null,
                ' ' => null,
                '1.1' => null,
                '2' => null,
                'string' => null,
                '[]' => null,
                '{}' => null,
                '.' => null,
                '*' => null,
                '\\' => null,
                '\n' => null,
            ],
        );

        $data[] = self::getRow(
            type: VariableType::STRING,
            values: [
                'example' => 'example',
                ' 123 ' => ' 123 ',
                '1.1' => '1.1',
                '-1 . 1' => '-1 . 1',
                'string' => 'string',
                'true' => 'true',
                'false' => 'false',
                '[]' => '[]',
                '{}' => '{}',
                '.' => '.',
                '*' => '*',
                '\\' => '\\',
                '\n' => '\n',
                // There are no invalid cases
            ],
        );

        $data[] = self::getRow(
            type: VariableType::INTEGER,
            values: [
                // Basic integer values
                '123' => 123,
                ' 123' => 123,
                ' 123 ' => 123,
                '123 ' => 123,
                '0' => 0,
                '-0' => 0,
                '-123' => -123,
                '- 123' => null,
                // Invalid values
                '1.1' => null,
                '-1 . 1' => null,
                'string' => null,
                'true' => null,
                'false' => null,
                '[]' => null,
                '{}' => null,
                '.' => null,
                '*' => null,
                '\\' => null,
                '\n' => null,
                // 32-bit integer range
                '2147483647' => 2147483647,
                '-2147483648' => -2147483648,
                // 32-bit integer range + 1
                '2147483648' => 2147483648,
                '-2147483649' => -2147483649,
                // 64-bit integer range
                '9223372036854775807' => 9223372036854775807,
                '-9223372036854775807' => -9223372036854775807,
                // 64-bit integer range + 1
                // This is an edge case. Neither option work correctly as PHP converts int value of -9223372036854775808 to a float.
                // String value '-9223372036854775808' is casted correctly to int, but cannot be compared as compared value becomes a float.
                // Leave as is, not a priority.
                // '-9223372036854775808' => null,
                // '-9223372036854775808' => -9223372036854775808,
                '-9223372036854775809' => null,
            ],
        );

        $data[] = self::getRow(
            type: VariableType::FLOAT,
            values: [
                // Basic float values
                '12.34' => 12.34,
                ' 12.34' => 12.34,
                ' 12.34 ' => 12.34,
                '12.34 ' => 12.34,
                '0' => 0.0,
                '-0' => 0.0,
                '-12.34' => -12.34,
                '- 12.34' => null,
                // Float values with scientific notations
                '1.2e3' => 1200.0,
                '-1.2e3' => -1200.0,
                '7E-3' => 0.007,
                '-7E-3' => -0.007,
                // Invalid values
                '-1 . 1' => null,
                'string' => null,
                'true' => null,
                'false' => null,
                '[]' => null,
                '{}' => null,
                '.' => null,
                '*' => null,
                '\\' => null,
                '\n' => null,
                // Testing float min/max and precision seems impractical. Skipped
            ],
        );

        $data[] = self::getRow(
            type: VariableType::JSON_OBJECT,
            values: [
                // Basic JSON values
                '{}' => [],
                '[]' => [],
                '{"key": "value"}' => ['key' => 'value'],
                '{"key": 123}' => ['key' => 123],
                '{"key": 12.34}' => ['key' => 12.34],
                '{"key": true}' => ['key' => true],
                '{"key": false}' => ['key' => false],
                '{"key": null}' => ['key' => null],
                // Nested JSON values
                '{"key": {"nestedKey": "nestedValue"}}' => ['key' => ['nestedKey' => 'nestedValue']],
                '{"key": [1, 2, 3]}' => ['key' => [1, 2, 3]],
                // Invalid JSON
                '{"key": "value"' => null, // Missing closing brace
                '{"key": "value",}' => null, // Trailing comma
                '{"key": "value}' => null, // Invalid quote
                // Edge cases for \json_decode (unquoted values true, false and null are returned as true, false and null respectively.)
                'null' => null,
                'true' => null,
                'false' => null,
                // Invalid values
                '0' => null,
                '1' => null,
                '12.34' => null,
                '-1 . 1' => null,
                'string' => null,
                'true' => null,
                'false' => null,
                '.' => null,
                '*' => null,
                '\\' => null,
                '\n' => null,
            ],
        );

        return $data;
    }

    public static function dataProvider(): array
    {
        return static::getProviderNamedData(static::getData(), static::getDataName(...));
    }

    public static function getDataName($row, $key): string
    {
        $names = [];
        $names[] = 'Variable type "'.$row[0]->value.'"';

        return implode('. ', $names);
    }

    #[DataProvider('dataProvider')]
    public function testTypeValidation(VariableType $variableType, array $values): void
    {
        $variableManager = $this->getService(VariableManager::class);

        foreach ($values as $value => $typedValue) {
            // Key can be converted to an int due to PHP behaviour. Convert it to string to make sure we test the correct value.
            $stringValue = (string) $value;
            $isValidType = $variableManager->isValidType($variableType, $stringValue);
            $expectValid = null !== $typedValue;
            if ($expectValid) {
                $this->assertTrue($isValidType, 'Value "'.$stringValue.'" should be valid');
            } else {
                $this->assertFalse($isValidType, 'Value "'.$stringValue.'" should not be valid');
            }

            if ($expectValid) {
                $typedValueResult = $variableManager->getTypedVariableValue($variableType, $stringValue);
                $typedValueAsString = is_array($typedValue) ? json_encode($typedValue) : (string) $typedValue;
                $this->assertSame($typedValue, $typedValueResult, 'Typed value for "'.$stringValue.'" should be the same as "'.$typedValueAsString.'"');
            }
        }
    }
}
