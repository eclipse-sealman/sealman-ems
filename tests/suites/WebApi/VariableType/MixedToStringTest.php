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

use App\Tool\TypeCaster;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\Abstract\AbstractTestCase;

/**
 * Tests mixedToString type casting.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class MixedToStringTest extends AbstractTestCase
{
    public static function getData(): array
    {
        $data = [];

        // Null value
        $data[] = ['inputValue' => null, 'expectedValue' => 'null'];
        // Bool values
        $data[] = ['inputValue' => true, 'expectedValue' => 'true'];
        $data[] = ['inputValue' => false, 'expectedValue' => 'false'];
        // Int values
        $data[] = ['inputValue' => 0, 'expectedValue' => '0'];
        $data[] = ['inputValue' => 1, 'expectedValue' => '1'];
        $data[] = ['inputValue' => -0, 'expectedValue' => '0'];
        $data[] = ['inputValue' => -1, 'expectedValue' => '-1'];
        $data[] = ['inputValue' => -2, 'expectedValue' => '-2'];
        $data[] = ['inputValue' => 2, 'expectedValue' => '2'];
        $data[] = ['inputValue' => 1234, 'expectedValue' => '1234'];
        $data[] = ['inputValue' => -1234, 'expectedValue' => '-1234'];
        $data[] = ['inputValue' => 2147483647, 'expectedValue' => '2147483647'];
        $data[] = ['inputValue' => -2147483648, 'expectedValue' => '-2147483648'];
        $data[] = ['inputValue' => 9223372036854775807, 'expectedValue' => '9223372036854775807'];
        $data[] = ['inputValue' => -9223372036854775807, 'expectedValue' => '-9223372036854775807'];
        // This is treated as float value
        $data[] = ['inputValue' => -9223372036854775809, 'expectedValue' => '-9.2233720368548E+18'];

        // Float values
        $data[] = ['inputValue' => 0.4321, 'expectedValue' => '0.4321'];
        $data[] = ['inputValue' => -0.4321, 'expectedValue' => '-0.4321'];
        $data[] = ['inputValue' => 0.0, 'expectedValue' => '0'];
        $data[] = ['inputValue' => -0.0, 'expectedValue' => '-0'];
        $data[] = ['inputValue' => 1.2e3, 'expectedValue' => '1200'];
        $data[] = ['inputValue' => -1.2e3, 'expectedValue' => '-1200'];
        $data[] = ['inputValue' => 7E-3, 'expectedValue' => '0.007'];
        $data[] = ['inputValue' => -7E-3, 'expectedValue' => '-0.007'];
        $data[] = ['inputValue' => 0.007, 'expectedValue' => '0.007'];
        $data[] = ['inputValue' => -0.007, 'expectedValue' => '-0.007'];

        // String values
        $data[] = ['inputValue' => '', 'expectedValue' => ''];
        $data[] = ['inputValue' => ' ', 'expectedValue' => ' '];
        $data[] = ['inputValue' => 'string', 'expectedValue' => 'string'];
        $data[] = ['inputValue' => 'AnotherString-DifferentOne-ThanAbove', 'expectedValue' => 'AnotherString-DifferentOne-ThanAbove'];
        $data[] = ['inputValue' => '[]', 'expectedValue' => '[]'];
        $data[] = ['inputValue' => '{}', 'expectedValue' => '{}'];
        $data[] = ['inputValue' => '.', 'expectedValue' => '.'];
        $data[] = ['inputValue' => '*', 'expectedValue' => '*'];
        $data[] = ['inputValue' => '\\', 'expectedValue' => '\\'];
        $data[] = ['inputValue' => '\n', 'expectedValue' => '\n'];
        $data[] = ['inputValue' => '{"key": 123}', 'expectedValue' => '{"key": 123}'];

        // Array values
        $data[] = ['inputValue' => ['key' => 'value'], 'expectedValue' => '{"key":"value"}'];
        $data[] = ['inputValue' => ['key' => 123], 'expectedValue' => '{"key":123}'];
        $data[] = ['inputValue' => ['key' => true], 'expectedValue' => '{"key":true}'];
        $data[] = ['inputValue' => ['key' => null], 'expectedValue' => '{"key":null}'];

        return $data;
    }

    public static function dataProvider(): array
    {
        return static::getProviderNamedData(static::getData(), static::getDataName(...));
    }

    public static function getDataName($row, $key): string
    {
        $names = [];
        $names[] = 'Test case #'.$key.' Input Value "'.\json_encode($row['inputValue']).'" Expected value "'.$row['expectedValue'].'"';

        return implode('. ', $names);
    }

    #[DataProvider('dataProvider')]
    public function testTypeValidation(null|bool|int|string|float|array $inputValue, string|null $expectedValue): void
    {
        $stringValue = TypeCaster::mixedToString($inputValue);

        $this->assertSame($expectedValue, $stringValue, 'String value for "'.$inputValue.'" should be the same as "'.$expectedValue.'"');
    }
}
