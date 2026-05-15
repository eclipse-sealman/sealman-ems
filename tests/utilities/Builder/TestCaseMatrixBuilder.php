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

namespace Tests\Utilities\Builder;

/**
 * Utility class for generating combinatorial test case matrices.
 *
 * Provides static methods for creating comprehensive test case matrices by combining
 * different parameter values in all possible combinations. Designed to eliminate
 * code duplication in PHPUnit data providers and ensure consistent combinatorial logic.
 *
 * Examples:
 * Basic single parameter combination
 * ```php
 * $cases = TestCaseMatrixBuilder::withParameter(
 *     'environment',
 *     ['dev' => 'development', 'prod' => 'production']
 * );
 * ```
 *
 * Multiple parameters combination
 * ```php
 * $cases = TestCaseMatrixBuilder::withParameterCombinations([
 *     'dev-https' => ['env' => 'dev', 'protocol' => 'https'],
 *     'prod-http' => ['env' => 'prod', 'protocol' => 'http']
 * ]);
 * ```
 */
class TestCaseMatrixBuilder
{
    /**
     * Adds a single parameter to test cases, creating all possible combinations.
     *
     * Creates a cartesian product between existing test cases and new parameter values,
     * resulting in comprehensive test coverage across all parameter combinations.
     *
     * @param string $name      Parameter name to add to each test case
     * @param array  $values    Parameter values as key-value pairs
     * @param array  $baseCases Existing test cases (optional)
     *
     * @return array<string, array<string, mixed>> Expanded test cases with new parameter
     *
     * Examples:
     * Basic usage - create base cases
     * ```php
     * $result = TestCaseMatrixBuilder::withParameter(
     *     'environment',
     *     ['dev' => 'development', 'prod' => 'production']
     * );
     *
     * Result:
     * [
     *     'dev' => ['environment' => 'development'],
     *     'prod' => ['environment' => 'production']
     * ]
     * ```
     * Advanced usage - extend existing cases
     * ```php
     * $baseCases = ['test1' => ['setting' => 'value1']];
     * $result = TestCaseMatrixBuilder::withParameter(
     *     'environment',
     *     ['dev' => 'development'],
     *     $baseCases
     * );
     *
     * Result:
     * [
     *     'test1-dev' => ['setting' => 'value1', 'environment' => 'development']
     * ]
     * ```
     */
    public static function withParameter(string $name, array $values, array $baseCases = []): array
    {
        // Convert single parameter values into the multi-parameter format
        $parameterCombinations = [];
        foreach ($values as $key => $value) {
            $parameterCombinations[$key] = [
                $name => $value,
            ];
        }

        // Use the withParameterCombinations method to do the actual work
        return self::withParameterCombinations($parameterCombinations, $baseCases);
    }

    /**
     * Adds multiple parameter combinations to test cases, creating all possible combinations.
     *
     * Core combinatorial engine that handles cartesian product generation for multiple parameters.
     * Useful for creating complex test matrices with multiple dimensions.
     *
     * @param array $combinations Parameter combinations (combinationKey => parameters)
     * @param array $baseCases    Existing test cases (optional)
     *
     * @return array<string, array<string, mixed>> Expanded test cases with all parameter combinations
     *
     * Examples:
     * Create base cases from combinations
     * ```php
     * $combinations = [
     *     'dev-https' => ['environment' => 'development', 'protocol' => 'https'],
     *     'prod-http' => ['environment' => 'production', 'protocol' => 'http']
     * ];
     * $result = TestCaseMatrixBuilder::withParameterCombinations($combinations);
     *
     * Result:
     * [
     *     'dev-https' => ['environment' => 'development', 'protocol' => 'https'],
     *     'prod-http' => ['environment' => 'production', 'protocol' => 'http']
     * ]
     * ```
     * Extend existing cases
     * ```php
     * $baseCases = ['test1' => ['database' => 'mysql']];
     * $result = TestCaseMatrixBuilder::withParameterCombinations($combinations, $baseCases);
     *
     * Result:
     * [
     *     'test1-dev-https' => ['database' => 'mysql', 'environment' => 'development', 'protocol' => 'https'],
     *     'test1-prod-http' => ['database' => 'mysql', 'environment' => 'production', 'protocol' => 'http']
     * ]
     * ```
     */
    public static function withParameterCombinations(array $combinations, array $baseCases = []): array
    {
        $generatedCases = [];

        // If no test cases provided, create a base case for each parameter combination
        if (empty($baseCases)) {
            foreach ($combinations as $combinationKey => $parameterValues) {
                $generatedCases[$combinationKey] = $parameterValues;
            }
        } else {
            // Combine existing test cases with parameter combinations
            foreach ($baseCases as $caseName => $caseData) {
                foreach ($combinations as $combinationKey => $parameterValues) {
                    $newCaseName = $caseName.'-'.$combinationKey;
                    $newCaseData = array_merge($caseData, $parameterValues);

                    $generatedCases[$newCaseName] = $newCaseData;
                }
            }
        }

        return $generatedCases;
    }

    /**
     * Converts an array of values into key-value pairs where each value becomes both key and value.
     *
     * Helper method that transforms array values into the format expected by combinatorial
     * helper methods. Each array value becomes both the key and value in the result.
     *
     * @param array<int, string> $values Array of values to transform
     *
     * @return array<string, string> Values as key-value pairs
     *
     * Basic usage
     * ```php
     * $input = ['value1', 'value2', 'value3'];
     * $result = TestCaseMatrixBuilder::normalizeValues($input);
     *
     * Returns:
     * [
     *     'value1' => 'value1',
     *     'value2' => 'value2',
     *     'value3' => 'value3'
     * ]
     * ```
     */
    public static function normalizeValues(array $values): array
    {
        $keyValuePairs = [];
        foreach ($values as $value) {
            $keyValuePairs[$value] = $value;
        }

        return $keyValuePairs;
    }

    /**
     * Converts an Enum class into key-value pairs where each enum case name becomes the key
     * and the corresponding enum value becomes the value.
     *
     * Helper method that transforms enum cases into the format expected by combinatorial
     * helper methods. Each enum case name becomes the key and its value becomes the value.
     *
     * @param class-string<\BackedEnum> $enumClass Enum class name
     * @param string                    $keyPrefix Optional prefix to add to each key
     *
     * @return array<string, mixed> Enum cases as key-value pairs
     *
     * Basic usage
     * ```php
     * enum Status: string {
     *     case ACTIVE = 'active';
     *     case INACTIVE = 'inactive';
     * }
     *
     * $result = TestCaseMatrixBuilder::getEnumValues(Status::class);
     *
     * Returns:
     * [
     *     'active' => 'active',
     *     'inactive' => 'inactive'
     * ]
     *
     * With prefix
     * $resultWithPrefix = TestCaseMatrixBuilder::getEnumValues(Status::class, 'status_');
     *
     * Returns:
     * [
     *     'status_active' => 'active',
     *     'status_inactive' => 'inactive'
     * ]
     * ```
     */
    public static function getEnumValues(string $enumClass, string $keyPrefix = ''): array
    {
        $keyValuePairs = [];
        foreach ($enumClass::cases() as $value) {
            $keyValuePairs[$keyPrefix.$value->value] = $value;
        }

        return $keyValuePairs;
    }
}
