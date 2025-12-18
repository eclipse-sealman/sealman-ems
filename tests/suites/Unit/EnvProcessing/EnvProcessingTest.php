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

namespace Tests\Suites\Unit\EnvProcessing;

use App\DependencyInjection\DoctrineSslExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Utilities\Utility\Accessor;

/**
 * Testing App\DependencyInjection\DoctrineSslExtension class.
 */
#[Group('full')]
#[Group('smoke')]
class EnvProcessingTest extends KernelTestCase
{
    public static function variableValueProvider(): array
    {
        return [
            'nullValue' => ['variableValue' => null, 'expectedValue' => false],
            'trueValue' => ['variableValue' => true, 'expectedValue' => true],
            'falseValue' => ['variableValue' => false, 'expectedValue' => false],
            'stringTrueValue' => ['variableValue' => 'true', 'expectedValue' => true],
            'stringFalseValue' => ['variableValue' => 'false', 'expectedValue' => false],
            'string1Value' => ['variableValue' => '1', 'expectedValue' => true],
            'string0Value' => ['variableValue' => '0', 'expectedValue' => false],
            'int1Value' => ['variableValue' => 1, 'expectedValue' => true],
            'int0Value' => ['variableValue' => 0, 'expectedValue' => false],
            'float1Value' => ['variableValue' => 1.0, 'expectedValue' => true],
            'float0Value' => ['variableValue' => 0.0, 'expectedValue' => false],
            'randomStringValue' => ['variableValue' => 'randomString', 'expectedValue' => false],
            'emptyStringValue' => ['variableValue' => '', 'expectedValue' => false],
            'arrayValue' => ['variableValue' => ['value1', 'value2'], 'expectedValue' => false],
            'emptyArrayValue' => ['variableValue' => [], 'expectedValue' => false],
        ];
    }

    #[DataProvider('variableValueProvider')]
    public function testGetValidatedBooleanValue(mixed $variableValue, bool $expectedValue)
    {
        $doctrineSslExtension = new DoctrineSslExtension();

        $validatedValue = Accessor::invoke($doctrineSslExtension, 'getValidatedBooleanValue', [$variableValue]);

        $this->assertSame($expectedValue, $validatedValue);
    }
}
