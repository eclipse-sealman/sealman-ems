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

namespace Tests\Suites\Unit\FirmwareUpdatePathManager;

use App\Enum\FirmwareVersionSchema;
use App\Service\FirmwareVersionSchemaManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Tests\Utilities\Utility\Accessor;

/**
 * Testing App\Service\FirmwareVersionSchemaManager class.
 *
 * Updated to test only the available schemas in FirmwareVersionSchema enum:
 * - ANY_SCHEMA: accepts any version string
 * - SEMANTIC_VERSIONING: validates using Composer\Semver
 * - V_SEMANTIC_VERSIONING: validates using Composer\Semver (strips 'v' prefix if present)
 *
 * Test cases are based on Composer\Semver valid and invalid version patterns.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Unit')] // To be used in CI parallel tests
class FirmwareVersionSchemaManagerTest extends TestCase
{
    private FirmwareVersionSchemaManager $manager;

    protected function setUp(): void
    {
        $this->manager = new FirmwareVersionSchemaManager();
    }

    public static function versionSchemaProvider(): array
    {
        return [
            // ANY_SCHEMA - always returns true regardless of version format
            'anySchema_validVersion' => [
                'schema' => FirmwareVersionSchema::ANY_SCHEMA,
                'version' => '1.2.3',
                'isValid' => true,
            ],
            'anySchema_invalidVersion' => [
                'schema' => FirmwareVersionSchema::ANY_SCHEMA,
                'version' => 'invalid-version',
                'isValid' => true,
            ],
            'anySchema_emptyVersion' => [
                'schema' => FirmwareVersionSchema::ANY_SCHEMA,
                'version' => '',
                'isValid' => true,
            ],
            'anySchema_complexVersion' => [
                'schema' => FirmwareVersionSchema::ANY_SCHEMA,
                'version' => 'anything-goes-here',
                'isValid' => true,
            ],

            // SEMANTIC_VERSIONING - uses Composer\Semver validation
            // Valid semantic versions according to Composer\Semver
            'semanticVersioning_valid_basic' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.2.3',
                'isValid' => true,
            ],
            'semanticVersioning_valid_zeros' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '0.0.0',
                'isValid' => true,
            ],
            'semanticVersioning_valid_large' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '123.456.789',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_alpha' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-alpha',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_alpha_num' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-alpha.1',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_beta' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-beta',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_test' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-rc.1',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_test1' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-rc.1',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_test2' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-rc1',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_test3' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-r1',
                'isValid' => false,
            ],
            'semanticVersioning_valid_prerelease_test4' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-r.1',
                'isValid' => false,
            ],
            'semanticVersioning_valid_prerelease_test5' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-r-1',
                'isValid' => false,
            ],
            'semanticVersioning_valid_prerelease_test6' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-p1',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_test7' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-p.1',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_test8' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-p-1',
                'isValid' => true,
            ],
            'semanticVersioning_valid_prerelease_test9' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-t1',
                'isValid' => false,
            ],
            'semanticVersioning_valid_prerelease_test10' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-t.1',
                'isValid' => false,
            ],
            'semanticVersioning_valid_prerelease_test11' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-t-1',
                'isValid' => false,
            ],
            'semanticVersioning_invalid_custom_versioning' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => 'SXL-25.04.16.000',
                'isValid' => false,
            ],
            'semanticVersioning_valid_build_metadata' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0+20130313144700',
                'isValid' => true,
            ],
            'semanticVersioning_valid_complex' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0.0-beta+exp.sha.5114f85',
                'isValid' => true,
            ],
            'semanticVersioning_valid_v_prefix' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => 'v1.0.0',
                'isValid' => false,
            ],
            'semanticVersioning_valid_v_prefix' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => 'vv1.0.0',
                'isValid' => false,
            ],
            'semanticVersioning_valid_v_prefix' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => 'a1.0.0',
                'isValid' => false,
            ],
            'semanticVersioning_invalid_two_digit' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.0',
                'isValid' => false,
            ],
            'semanticVersioning_invalid_single_digit' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1',
                'isValid' => false,
            ],

            // Invalid semantic versions according to Composer\Semver
            'semanticVersioning_invalid_empty' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '',
                'isValid' => false,
            ],
            'semanticVersioning_invalid_non_numeric' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => 'a.b.c',
                'isValid' => false,
            ],
            'semanticVersioning_invalid_spaces' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.2 .3',
                'isValid' => false,
            ],
            'semanticVersioning_invalid_special_chars' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => '1.2.3@#$',
                'isValid' => false,
            ],
            'semanticVersioning_invalid_random_text' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version' => 'not-a-version',
                'isValid' => false,
            ],

            // V_SEMANTIC_VERSIONING - uses Composer\Semver validation but requires 'v' prefix
            // Valid v-prefixed semantic versions
            'vSemanticVersioning_valid_basic' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1.2.3',
                'isValid' => true,
            ],
            'vSemanticVersioning_valid_v_prefix' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1.0.0',
                'isValid' => true,
            ],
            'vSemanticVersioning_valid_v_prefix' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'vv1.0.0',
                'isValid' => false,
            ],
            'vSemanticVersioning_valid_no_v_prefix' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => '1.0.0',
                'isValid' => false,
            ],
            'vSemanticVersioning_valid_v_prefix' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'a1.0.0',
                'isValid' => false,
            ],
            'vSemanticVersioning_valid_zeros' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v0.0.0',
                'isValid' => true,
            ],
            'vSemanticVersioning_valid_large' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v123.456.789',
                'isValid' => true,
            ],
            'vSemanticVersioning_valid_prerelease_alpha' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1.0.0-alpha',
                'isValid' => true,
            ],
            'vSemanticVersioning_valid_prerelease_beta' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1.0.0-beta.1',
                'isValid' => true,
            ],
            'vSemanticVersioning_valid_prerelease_rc' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1.0.0-rc.1',
                'isValid' => true,
            ],
            'vSemanticVersioning_valid_build_metadata' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1.0.0+build.123',
                'isValid' => true,
            ],
            'vSemanticVersioning_valid_complex' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v2.0.0-rc.1+build.123',
                'isValid' => true,
            ],
            'vSemanticVersioning_invalid_two_digit' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1.0',
                'isValid' => false,
            ],
            'vSemanticVersioning_invalid_single_digit' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1',
                'isValid' => false,
            ],

            // Invalid v-prefixed semantic versions
            // Note: Current implementation accepts any valid semver, regardless of 'v' prefix
            'vSemanticVersioning_invalid_no_prefix' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => '1.2.3',
                'isValid' => false,
            ],
            'vSemanticVersioning_invalid_capital_v' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'V1.2.3',
                'isValid' => false,
            ],
            'vSemanticVersioning_invalid_empty' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => '',
                'isValid' => false,
            ],
            'vSemanticVersioning_invalid_v_only' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v',
                'isValid' => false,
            ],
            'vSemanticVersioning_invalid_non_numeric' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'va.b.c',
                'isValid' => false,
            ],
            'vSemanticVersioning_invalid_spaces' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'v1.2 .3',
                'isValid' => false,
            ],
            'vSemanticVersioning_invalid_wrong_prefix' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'version1.2.3',
                'isValid' => false,
            ],
            'vSemanticVersioning_invalid_random_text' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version' => 'vnot-a-version',
                'isValid' => false,
            ],

            // EG_OS_SCHEMA - EdgeGateway OS versioning Major.Minor.Patch[_preRelease]
            // Valid EG_OS versions
            'egOsSchema_valid_basic' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3',
                'isValid' => true,
            ],
            'egOsSchema_valid_zeros' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '0.0.0',
                'isValid' => true,
            ],
            'egOsSchema_valid_large_numbers' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '123.456.789',
                'isValid' => true,
            ],
            'egOsSchema_valid_with_prerelease_alpha' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3_alpha',
                'isValid' => true,
            ],
            'egOsSchema_valid_with_prerelease_beta' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3_beta',
                'isValid' => true,
            ],
            'egOsSchema_valid_with_prerelease_rc' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3_rc1',
                'isValid' => true,
            ],
            'egOsSchema_valid_with_prerelease_numeric' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3_1',
                'isValid' => true,
            ],
            'egOsSchema_valid_with_prerelease_alphanumeric' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3_alpha1',
                'isValid' => true,
            ],
            'egOsSchema_valid_with_prerelease_mixed' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '2.0.1_beta2test',
                'isValid' => true,
            ],
            'egOsSchema_valid_single_digit' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.0.0',
                'isValid' => true,
            ],
            'egOsSchema_valid_no_leading_zeros' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '10.20.30',
                'isValid' => true,
            ],

            // Invalid EG_OS versions
            'egOsSchema_invalid_empty' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '',
                'isValid' => false,
            ],
            'egOsSchema_invalid_two_parts' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2',
                'isValid' => false,
            ],
            'egOsSchema_invalid_single_part' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1',
                'isValid' => false,
            ],
            'egOsSchema_invalid_four_parts' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3.4',
                'isValid' => false,
            ],
            'egOsSchema_invalid_leading_zeros_major' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '01.2.3',
                'isValid' => false,
            ],
            'egOsSchema_invalid_leading_zeros_minor' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.02.3',
                'isValid' => false,
            ],
            'egOsSchema_invalid_leading_zeros_patch' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.03',
                'isValid' => false,
            ],
            'egOsSchema_invalid_negative_numbers' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '-1.2.3',
                'isValid' => false,
            ],
            'egOsSchema_invalid_non_numeric' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => 'a.b.c',
                'isValid' => false,
            ],
            'egOsSchema_invalid_spaces' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2 .3',
                'isValid' => false,
            ],
            'egOsSchema_invalid_dash_prerelease' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3-alpha',
                'isValid' => false,
            ],
            'egOsSchema_invalid_dot_prerelease' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3.alpha',
                'isValid' => false,
            ],
            'egOsSchema_invalid_double_underscore' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3__alpha',
                'isValid' => false,
            ],
            'egOsSchema_invalid_underscore_only' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3_',
                'isValid' => false,
            ],
            'egOsSchema_invalid_special_chars_in_prerelease' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => '1.2.3_alpha@',
                'isValid' => false,
            ],
            'egOsSchema_invalid_with_v_prefix' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => 'v1.2.3',
                'isValid' => false,
            ],
            'egOsSchema_invalid_random_text' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version' => 'not-a-version',
                'isValid' => false,
            ],
        ];
    }

    #[DataProvider('versionSchemaProvider')]
    public function testIsVersionValid(FirmwareVersionSchema $schema, string $version, bool $isValid): void
    {
        $result = $this->manager->isVersionValid($version, $schema);

        $this->assertSame(
            $isValid,
            $result,
            "Version '$version' validation against schema '{$schema->value}' should return ".($isValid ? 'true' : 'false')
        );
    }

    /**
     * Test specific examples for semantic versioning schemas.
     */
    public function testSemanticVersioningExamples(): void
    {
        // Test semantic versioning examples
        $this->assertTrue($this->manager->isVersionValid('1.2.3', FirmwareVersionSchema::SEMANTIC_VERSIONING));
        $this->assertTrue($this->manager->isVersionValid('1.0.0-alpha', FirmwareVersionSchema::SEMANTIC_VERSIONING));
        $this->assertFalse($this->manager->isVersionValid('v1.2.3', FirmwareVersionSchema::SEMANTIC_VERSIONING));

        // Test v-prefixed semantic versioning examples
        $this->assertTrue($this->manager->isVersionValid('v1.2.3', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));
        $this->assertTrue($this->manager->isVersionValid('v1.0.0-alpha', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));
        $this->assertTrue($this->manager->isVersionValid('v123.56.65', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));

        // Test that non-v-prefixed versions are accepted by V_SEMANTIC_VERSIONING (current implementation behavior)
        $this->assertFalse($this->manager->isVersionValid('1.2.3', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));

        // Test ANY_SCHEMA accepts everything
        $this->assertTrue($this->manager->isVersionValid('anything', FirmwareVersionSchema::ANY_SCHEMA));
        $this->assertTrue($this->manager->isVersionValid('', FirmwareVersionSchema::ANY_SCHEMA));

        // Test EG_OS_SCHEMA examples
        $this->assertTrue($this->manager->isVersionValid('1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersionValid('1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersionValid('0.0.0', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersionValid('123.456.789_beta1', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('1.2', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('v1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('1.2.3-alpha', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('01.2.3', FirmwareVersionSchema::EG_OS_SCHEMA));
    }

    public static function versionComparisonProvider(): array
    {
        return [
            // SEMANTIC_VERSIONING schema comparisons
            'semanticVersioning_equal' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.2.3',
                'version2' => '1.2.3',
                'operator' => '==',
                'expectedResult' => true,
            ],
            'semanticVersioning_greater_than' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.2.4',
                'version2' => '1.2.3',
                'operator' => '>',
                'expectedResult' => true,
            ],
            'semanticVersioning_less_than' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '<',
                'expectedResult' => true,
            ],
            'semanticVersioning_greater_equal' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.2.3',
                'version2' => '1.2.3',
                'operator' => '>=',
                'expectedResult' => true,
            ],
            'semanticVersioning_less_equal' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '<=',
                'expectedResult' => true,
            ],
            'semanticVersioning_not_equal' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '!=',
                'expectedResult' => true,
            ],
            'semanticVersioning_major_version_diff' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '2.0.0',
                'version2' => '1.9.9',
                'operator' => '>',
                'expectedResult' => true,
            ],
            'semanticVersioning_prerelease_vs_release' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.0.0-alpha',
                'version2' => '1.0.0',
                'operator' => '<',
                'expectedResult' => true,
            ],

            // V_SEMANTIC_VERSIONING schema comparisons
            'vSemanticVersioning_equal' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version1' => 'v1.2.3',
                'version2' => 'v1.2.3',
                'operator' => '==',
                'expectedResult' => true,
            ],
            'vSemanticVersioning_greater_than' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version1' => 'v1.2.4',
                'version2' => 'v1.2.3',
                'operator' => '>',
                'expectedResult' => true,
            ],
            'vSemanticVersioning_less_than' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version1' => 'v1.2.3',
                'version2' => 'v1.2.4',
                'operator' => '<',
                'expectedResult' => true,
            ],
            'vSemanticVersioning_greater_equal' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version1' => 'v1.2.3',
                'version2' => 'v1.2.3',
                'operator' => '>=',
                'expectedResult' => true,
            ],
            'vSemanticVersioning_major_version_diff' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version1' => 'v2.0.0',
                'version2' => 'v1.9.9',
                'operator' => '>',
                'expectedResult' => true,
            ],
            'vSemanticVersioning_prerelease_vs_release' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version1' => 'v1.0.0-alpha',
                'version2' => 'v1.0.0',
                'operator' => '<',
                'expectedResult' => true,
            ],
            'vSemanticVersioning_zeros_vs_numbers' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version1' => 'v0.0.0',
                'version2' => 'v0.0.1',
                'operator' => '<',
                'expectedResult' => true,
            ],

            // False comparison cases
            'semanticVersioning_false_equal' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '==',
                'expectedResult' => false,
            ],
            'semanticVersioning_false_greater' => [
                'schema' => FirmwareVersionSchema::SEMANTIC_VERSIONING,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '>',
                'expectedResult' => false,
            ],
            'vSemanticVersioning_false_equal' => [
                'schema' => FirmwareVersionSchema::V_SEMANTIC_VERSIONING,
                'version1' => 'v1.2.3',
                'version2' => 'v1.2.4',
                'operator' => '==',
                'expectedResult' => false,
            ],

            // EG_OS_SCHEMA schema comparisons
            'egOsSchema_equal_basic' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.3',
                'operator' => '==',
                'expectedResult' => true,
            ],
            'egOsSchema_equal_with_prerelease' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3_alpha',
                'version2' => '1.2.3_alpha',
                'operator' => '==',
                'expectedResult' => true,
            ],
            'egOsSchema_not_equal_basic' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '!=',
                'expectedResult' => true,
            ],
            'egOsSchema_not_equal_prerelease' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3_alpha',
                'version2' => '1.2.3_beta',
                'operator' => '!=',
                'expectedResult' => true,
            ],
            'egOsSchema_greater_than_patch' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.4',
                'version2' => '1.2.3',
                'operator' => '>',
                'expectedResult' => true,
            ],
            'egOsSchema_greater_than_minor' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.3.0',
                'version2' => '1.2.9',
                'operator' => '>',
                'expectedResult' => true,
            ],
            'egOsSchema_greater_than_major' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '2.0.0',
                'version2' => '1.9.9',
                'operator' => '>',
                'expectedResult' => true,
            ],
            'egOsSchema_less_than_patch' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '<',
                'expectedResult' => true,
            ],
            'egOsSchema_greater_equal_same' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.3',
                'operator' => '>=',
                'expectedResult' => true,
            ],
            'egOsSchema_greater_equal_higher' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.4',
                'version2' => '1.2.3',
                'operator' => '>=',
                'expectedResult' => true,
            ],
            'egOsSchema_less_equal_same' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.3',
                'operator' => '<=',
                'expectedResult' => true,
            ],
            'egOsSchema_less_equal_lower' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '<=',
                'expectedResult' => true,
            ],

            // EG_OS_SCHEMA prerelease comparisons (alphabetical order when semantic versions are equal)
            'egOsSchema_prerelease_vs_release' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.3_alpha',
                'operator' => '>',
                'expectedResult' => true, // Release version is greater than prerelease
            ],
            'egOsSchema_release_vs_prerelease' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3_alpha',
                'version2' => '1.2.3',
                'operator' => '>',
                'expectedResult' => false, // Prerelease version is less than release
            ],
            'egOsSchema_prerelease_alpha_vs_beta' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3_alpha',
                'version2' => '1.2.3_beta',
                'operator' => '<',
                'expectedResult' => true, // "alpha" < "beta" alphabetically
            ],
            'egOsSchema_prerelease_beta_vs_alpha' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3_beta',
                'version2' => '1.2.3_alpha',
                'operator' => '>',
                'expectedResult' => true, // "beta" > "alpha" alphabetically
            ],
            'egOsSchema_prerelease_rc1_vs_rc2' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3_rc1',
                'version2' => '1.2.3_rc2',
                'operator' => '<',
                'expectedResult' => true, // "rc1" < "rc2" alphabetically
            ],
            'egOsSchema_prerelease_equal' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3_alpha',
                'version2' => '1.2.3_alpha',
                'operator' => '==',
                'expectedResult' => true,
            ],

            // EG_OS_SCHEMA mixed version comparisons
            'egOsSchema_different_semantic_with_prerelease' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.4_alpha',
                'version2' => '1.2.3_beta',
                'operator' => '>',
                'expectedResult' => true, // Semantic version takes priority
            ],
            'egOsSchema_zeros_comparison' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '0.0.1',
                'version2' => '0.0.0',
                'operator' => '>',
                'expectedResult' => true,
            ],

            // False comparison cases for EG_OS_SCHEMA
            'egOsSchema_false_equal' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '==',
                'expectedResult' => false,
            ],
            'egOsSchema_false_greater' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.3',
                'version2' => '1.2.4',
                'operator' => '>',
                'expectedResult' => false,
            ],
            'egOsSchema_false_less' => [
                'schema' => FirmwareVersionSchema::EG_OS_SCHEMA,
                'version1' => '1.2.4',
                'version2' => '1.2.3',
                'operator' => '<',
                'expectedResult' => false,
            ],
        ];
    }

    #[DataProvider('versionComparisonProvider')]
    public function testCompareVersions(FirmwareVersionSchema $schema, string $version1, string $version2, string $operator, bool $expectedResult): void
    {
        $result = $this->manager->compareVersions($version1, $version2, $schema, $operator);

        $this->assertSame(
            $expectedResult,
            $result,
            "Comparing '$version1' $operator '$version2' with schema '{$schema->value}' should return ".($expectedResult ? 'true' : 'false')
        );
    }

    public function testCompareVersionsWithInvalidOperatorThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid comparison operator: ===');

        $this->manager->compareVersions('1.2.3', '1.2.4', FirmwareVersionSchema::SEMANTIC_VERSIONING, '===');
    }

    public function testCompareVersionsWithInvalidVersion1ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Version 'invalid-version' is not valid for schema 'semanticVersioning'");

        $this->manager->compareVersions('invalid-version', '1.2.3', FirmwareVersionSchema::SEMANTIC_VERSIONING, '>');
    }

    public function testCompareVersionsWithInvalidVersion2ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Version 'invalid-version' is not valid for schema 'semanticVersioning'");

        $this->manager->compareVersions('1.2.3', 'invalid-version', FirmwareVersionSchema::SEMANTIC_VERSIONING, '>');
    }

    public function testCompareVersionsWithValidVersions(): void
    {
        // Test that both schemas only accept valid versions
        $result = $this->manager->compareVersions('1.2.4', '1.2.3', FirmwareVersionSchema::SEMANTIC_VERSIONING, '>');
        $this->assertTrue($result);

        $result = $this->manager->compareVersions('v1.2.4', 'v1.2.3', FirmwareVersionSchema::V_SEMANTIC_VERSIONING, '>');
        $this->assertTrue($result);

        // Test EG_OS_SCHEMA valid versions
        $result = $this->manager->compareVersions('1.2.4', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA, '>');
        $this->assertTrue($result);

        $result = $this->manager->compareVersions('1.2.3_beta', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA, '>');
        $this->assertTrue($result);
    }

    public function testCompareVersionsWithEgOsSchemaInvalidVersion1ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Version '1.2' is not valid for schema 'egOsSchema'");

        $this->manager->compareVersions('1.2', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA, '>');
    }

    public function testCompareVersionsWithEgOsSchemaInvalidVersion2ThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Version 'v1.2.3' is not valid for schema 'egOsSchema'");

        $this->manager->compareVersions('1.2.3', 'v1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA, '>');
    }

    public function testCompareVersionsWithEgOsSchemaUnsupportedOperatorThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid comparison operator: ~');

        $this->manager->compareVersions('1.2.3', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA, '~');
    }

    public function testIsVersion1HigherThanVersion2(): void
    {
        // Test semantic versioning comparisons
        $this->assertTrue($this->manager->isVersion1HigherThanVersion2('1.2.4', '1.2.3', FirmwareVersionSchema::SEMANTIC_VERSIONING));
        $this->assertFalse($this->manager->isVersion1HigherThanVersion2('1.2.3', '1.2.4', FirmwareVersionSchema::SEMANTIC_VERSIONING));
        $this->assertFalse($this->manager->isVersion1HigherThanVersion2('1.2.3', '1.2.3', FirmwareVersionSchema::SEMANTIC_VERSIONING));

        // Test v-prefixed semantic versioning comparisons
        $this->assertTrue($this->manager->isVersion1HigherThanVersion2('v2.0.0', 'v1.9.9', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));
        $this->assertFalse($this->manager->isVersion1HigherThanVersion2('v1.2.3', 'v1.2.4', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));

        // Test prerelease vs release
        $this->assertTrue($this->manager->isVersion1HigherThanVersion2('1.0.0', '1.0.0-alpha', FirmwareVersionSchema::SEMANTIC_VERSIONING));
        $this->assertFalse($this->manager->isVersion1HigherThanVersion2('1.0.0-alpha', '1.0.0', FirmwareVersionSchema::SEMANTIC_VERSIONING));

        // Test EG_OS_SCHEMA comparisons
        $this->assertTrue($this->manager->isVersion1HigherThanVersion2('1.2.4', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersion1HigherThanVersion2('1.2.3', '1.2.4', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersion1HigherThanVersion2('1.2.3', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA));

        // Test EG_OS_SCHEMA with prerelease versions - alphabetical comparison when semantic versions are equal
        $this->assertTrue($this->manager->isVersion1HigherThanVersion2('1.2.3_beta', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersion1HigherThanVersion2('1.2.3_alpha', '1.2.3_beta', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersion1HigherThanVersion2('1.2.3_alpha', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA)); // prerelease < release (empty string)
        $this->assertTrue($this->manager->isVersion1HigherThanVersion2('1.2.3', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA)); // release > prerelease

        // Test EG_OS_SCHEMA major version differences
        $this->assertTrue($this->manager->isVersion1HigherThanVersion2('2.0.0', '1.9.9', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersion1HigherThanVersion2('2.0.0_alpha', '1.9.9_beta', FirmwareVersionSchema::EG_OS_SCHEMA));
    }

    public function testIsVersion1HigherThanOrEqualToVersion2(): void
    {
        // Test semantic versioning comparisons
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.4', '1.2.3', FirmwareVersionSchema::SEMANTIC_VERSIONING));
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3', '1.2.3', FirmwareVersionSchema::SEMANTIC_VERSIONING));
        $this->assertFalse($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3', '1.2.4', FirmwareVersionSchema::SEMANTIC_VERSIONING));

        // Test v-prefixed semantic versioning comparisons
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('v2.0.0', 'v1.9.9', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('v1.2.3', 'v1.2.3', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));
        $this->assertFalse($this->manager->isVersion1HigherThanOrEqualToVersion2('v1.2.3', 'v1.2.4', FirmwareVersionSchema::V_SEMANTIC_VERSIONING));

        // Test EG_OS_SCHEMA comparisons
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.4', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3', '1.2.4', FirmwareVersionSchema::EG_OS_SCHEMA));

        // Test EG_OS_SCHEMA with prerelease versions
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3_beta', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3_alpha', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3_alpha', '1.2.3_beta', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3_alpha', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA)); // prerelease < release
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('1.2.3', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA)); // release > prerelease

        // Test EG_OS_SCHEMA with different semantic versions
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('2.0.0', '1.9.9', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersion1HigherThanOrEqualToVersion2('1.3.0_alpha', '1.2.9_beta', FirmwareVersionSchema::EG_OS_SCHEMA));
    }

    /**
     * Test EG_OS_SCHEMA version part extraction using protected method.
     */
    public function testEgOsSchemaVersionPartExtraction(): void
    {
        // Test production version part extraction
        $productionVersion = Accessor::invoke($this->manager, 'getEgOsSchemaProductionVersionPart', ['1.2.3_alpha']);
        $this->assertSame('1.2.3', $productionVersion);

        $productionVersion = Accessor::invoke($this->manager, 'getEgOsSchemaProductionVersionPart', ['0.0.1']);
        $this->assertSame('0.0.1', $productionVersion);

        // Test prerelease version part extraction
        $preReleaseVersion = Accessor::invoke($this->manager, 'getEgOsSchemaPreReleaseVersionPart', ['1.2.3_alpha']);
        $this->assertSame('alpha', $preReleaseVersion);

        $preReleaseVersion = Accessor::invoke($this->manager, 'getEgOsSchemaPreReleaseVersionPart', ['1.2.3']);
        $this->assertSame('', $preReleaseVersion);

        $preReleaseVersion = Accessor::invoke($this->manager, 'getEgOsSchemaPreReleaseVersionPart', ['2.0.1_beta123']);
        $this->assertSame('beta123', $preReleaseVersion);
    }

    /**
     * Test EG_OS_SCHEMA version part extraction with invalid versions.
     */
    public function testEgOsSchemaVersionPartExtractionWithInvalidVersions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Version '1.2' is not valid for schema 'EG_OS_SCHEMA'");

        Accessor::invoke($this->manager, 'getEgOsSchemaProductionVersionPart', ['1.2']);
    }

    /**
     * Test EG_OS_SCHEMA prerelease part extraction with invalid versions.
     */
    public function testEgOsSchemaPreReleasePartExtractionWithInvalidVersions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Version 'invalid' is not valid for schema 'EG_OS_SCHEMA'");

        Accessor::invoke($this->manager, 'getEgOsSchemaPreReleaseVersionPart', ['invalid']);
    }

    /**
     * Test EG_OS_SCHEMA specific comparison logic with all operators.
     */
    public function testEgOsSchemaSpecificComparisons(): void
    {
        // Test all operators with equal semantic versions but different prereleases
        $this->assertTrue($this->manager->compareVersions('1.2.3_beta', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA, '>'));
        $this->assertTrue($this->manager->compareVersions('1.2.3_beta', '1.2.3_beta', FirmwareVersionSchema::EG_OS_SCHEMA, '>='));
        $this->assertTrue($this->manager->compareVersions('1.2.3_alpha', '1.2.3_beta', FirmwareVersionSchema::EG_OS_SCHEMA, '<'));
        $this->assertTrue($this->manager->compareVersions('1.2.3_alpha', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA, '<='));
        $this->assertTrue($this->manager->compareVersions('1.2.3_alpha', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA, '=='));
        $this->assertTrue($this->manager->compareVersions('1.2.3_alpha', '1.2.3_beta', FirmwareVersionSchema::EG_OS_SCHEMA, '!='));

        // Test with release vs prerelease (empty prerelease vs non-empty)
        $this->assertTrue($this->manager->compareVersions('1.2.3', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA, '>'));
        $this->assertFalse($this->manager->compareVersions('1.2.3_alpha', '1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA, '>'));

        // Test with different semantic versions (should prioritize semantic comparison)
        $this->assertTrue($this->manager->compareVersions('1.2.4_alpha', '1.2.3_zulu', FirmwareVersionSchema::EG_OS_SCHEMA, '>'));
        $this->assertFalse($this->manager->compareVersions('1.2.2_zulu', '1.2.3_alpha', FirmwareVersionSchema::EG_OS_SCHEMA, '>'));
    }

    /**
     * Test EG_OS_SCHEMA validation edge cases.
     */
    public function testEgOsSchemaValidationEdgeCases(): void
    {
        // Test various valid prerelease formats
        $this->assertTrue($this->manager->isVersionValid('1.0.0_a', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersionValid('1.0.0_1', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersionValid('1.0.0_A1b2C3', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersionValid('999.999.999_test123', FirmwareVersionSchema::EG_OS_SCHEMA));

        // Test various invalid formats
        $this->assertFalse($this->manager->isVersionValid('1.0.0_', FirmwareVersionSchema::EG_OS_SCHEMA)); // underscore with nothing after
        $this->assertFalse($this->manager->isVersionValid('1.0.0__alpha', FirmwareVersionSchema::EG_OS_SCHEMA)); // double underscore
        $this->assertFalse($this->manager->isVersionValid('1.0.0_alpha_beta', FirmwareVersionSchema::EG_OS_SCHEMA)); // multiple underscores
        $this->assertFalse($this->manager->isVersionValid('1.0.0_alpha-beta', FirmwareVersionSchema::EG_OS_SCHEMA)); // dash in prerelease
        $this->assertFalse($this->manager->isVersionValid('1.0.0_alpha.beta', FirmwareVersionSchema::EG_OS_SCHEMA)); // dot in prerelease
        $this->assertFalse($this->manager->isVersionValid('1.0.0_alpha@', FirmwareVersionSchema::EG_OS_SCHEMA)); // special character in prerelease

        // Test leading zeros (should be invalid)
        $this->assertFalse($this->manager->isVersionValid('01.0.0', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('1.00.0', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('1.0.00', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('01.00.00_alpha', FirmwareVersionSchema::EG_OS_SCHEMA));

        // Test boundary cases
        $this->assertTrue($this->manager->isVersionValid('0.0.0_0', FirmwareVersionSchema::EG_OS_SCHEMA)); // zeros with numeric prerelease
        $this->assertFalse($this->manager->isVersionValid('1.2.3.4', FirmwareVersionSchema::EG_OS_SCHEMA)); // too many parts
        $this->assertFalse($this->manager->isVersionValid('.1.2.3', FirmwareVersionSchema::EG_OS_SCHEMA)); // starts with dot
        $this->assertFalse($this->manager->isVersionValid('1.2.3.', FirmwareVersionSchema::EG_OS_SCHEMA)); // ends with dot
    }

    /**
     * Test EG_OS_SCHEMA with various numeric edge cases.
     */
    public function testEgOsSchemaNumericEdgeCases(): void
    {
        // Test large numbers
        $this->assertTrue($this->manager->isVersionValid('999999.999999.999999', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertTrue($this->manager->isVersionValid('999999.999999.999999_alpha999', FirmwareVersionSchema::EG_OS_SCHEMA));

        // Test comparison with large numbers
        $this->assertTrue($this->manager->compareVersions('1000.0.0', '999.999.999', FirmwareVersionSchema::EG_OS_SCHEMA, '>'));
        $this->assertTrue($this->manager->compareVersions('1.1000.0', '1.999.999', FirmwareVersionSchema::EG_OS_SCHEMA, '>'));
        $this->assertTrue($this->manager->compareVersions('1.0.1000', '1.0.999', FirmwareVersionSchema::EG_OS_SCHEMA, '>'));

        // Test negative numbers (should be invalid)
        $this->assertFalse($this->manager->isVersionValid('-1.0.0', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('1.-1.0', FirmwareVersionSchema::EG_OS_SCHEMA));
        $this->assertFalse($this->manager->isVersionValid('1.0.-1', FirmwareVersionSchema::EG_OS_SCHEMA));
    }
}
