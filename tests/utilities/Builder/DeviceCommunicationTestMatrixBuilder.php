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
 * Specialized builder for device communication test case matrices.
 *
 * Extends TestCaseMatrixBuilder with device-specific combinatorial methods for creating
 * comprehensive test matrices for device communication scenarios. Handles combinations of
 * device types, secrets/certificates configurations, and reinstall flags.
 *
 * Examples:
 * Device communication test combinations
 * ```php
 * $cases = DeviceCommunicationTestMatrixBuilder::buildDeviceTypesWithReinstallFlagsMatrix();
 * ```
 * Device type configurations
 * ```php
 * $cases = DeviceCommunicationTestMatrixBuilder::buildDeviceTypesSecretsCertificatesMatrix();
 * ```
 */
class DeviceCommunicationTestMatrixBuilder extends TestCaseMatrixBuilder
{
    /**
     * Generates comprehensive device communication test combinations with all reinstall flag configurations.
     *
     * Creates complete test matrix combining device types with all possible reinstall flags.
     * Results in device type × reinstall flags combinations (default: 11 device types × 4 configurations × 64 flag combinations = 2816 total cases).
     *
     * @param array<int, string>|null $deviceTypePrefixes Optional array of device type prefixes. If null, uses getSupportedDeviceTypePrefixes()
     *
     * @return array<string, array<mixed>> PHPUnit data provider formatted test cases
     *
     * Additional information:
     * - buildDeviceTypesSecretsCertificatesMatrix() For device type combinations
     * - buildReinstallFlagsPermutations() For reinstall flag combinations
     *
     * Basic usage:
     * ```php
     * $testCases = DeviceCommunicationTestMatrixBuilder::buildDeviceTypesWithReinstallFlagsMatrix();
     *
     * $testCases = DeviceCommunicationTestMatrixBuilder::buildDeviceTypesWithReinstallFlagsMatrix(['edge-gateway', 'vcc']);
     *
     * Returns array with keys like:
     * 'edge-gateway-none-none-rf1:F,rf2:F,rf3:F,rc1:F,rc2:F,rc3:F'
     *
     * Each value contains: [deviceConfig, reinstallFlags]
     * ```
     */
    public static function buildDeviceTypesWithReinstallFlagsMatrix(?array $deviceTypePrefixes = null): array
    {
        // Get all device type and configuration combinations as base test cases
        $deviceTypeCombinations = self::buildDeviceTypesSecretsCertificatesMatrix($deviceTypePrefixes);

        // Generate all possible reinstall flags combinations (2^6 = 64 combinations)
        $reinstallFlagsCombinations = self::buildReinstallFlagsPermutations();

        // Combine device types with reinstall flags using the matrix builder
        $allCombinations = [];
        foreach ($deviceTypeCombinations as $deviceKey => $deviceConfig) {
            // Convert device config to the format expected by the matrix builder
            $deviceTestCase = [$deviceKey => ['deviceConfig' => $deviceConfig]];

            // Generate combinations with reinstall flags
            $combinationsForThisDevice = self::withParameter(
                name: 'reinstallFlags',
                values: $reinstallFlagsCombinations,
                baseCases: $deviceTestCase
            );

            // Merge combinations for this device into the main array
            $allCombinations = array_merge($allCombinations, $combinationsForThisDevice);
        }

        // Format for PHPUnit data provider (extract device config and reinstall flags)
        $providerData = [];
        foreach ($allCombinations as $key => $caseData) {
            $providerData[$key] = [
                $caseData['deviceConfig'][0], // Device type configuration
                $caseData['reinstallFlags'],   // Reinstall flags configuration
            ];
        }

        return $providerData;
    }

    /**
     * Generates all possible combinations of reinstall flags for firmware and configuration features.
     *
     * Creates 64 combinations (2^6) covering all true/false combinations for 6 reinstall flags:
     * reinstallFirmware1-3 and reinstallConfig1-3.
     *
     * @return array<string, array<string, bool>> Reinstall flags combinations with descriptive keys
     *
     * Basic usage:
     * ```php
     * $combinations = DeviceCommunicationTestMatrixBuilder::buildReinstallFlagsPermutations();
     *
     * Returns:
     * [
     *     'rf1:F-rf2:F-rf3:F-rc1:F-rc2:F-rc3:F' => [
     *         'reinstallFirmware1' => false,
     *         'reinstallFirmware2' => false,
     *         'reinstallFirmware3' => false,
     *         'reinstallConfig1' => false,
     *         'reinstallConfig2' => false,
     *         'reinstallConfig3' => false
     *     ],
     *     'rf1:T-rf2:F-rf3:F-rc1:F-rc2:F-rc3:F' => [
     *         'reinstallFirmware1' => true,
     *         'reinstallFirmware2' => false,
     *         'reinstallFirmware3' => false,
     *         'reinstallConfig1' => false,
     *         'reinstallConfig2' => false,
     *         'reinstallConfig3' => false
     *     ],
     *     ... (62 more combinations)
     * ]
     * ```
     */
    public static function buildReinstallFlagsPermutations(): array
    {
        // Step 1: Start with reinstallFirmware1 parameter
        $withFirmware1 = self::withParameter(
            name: 'reinstallFirmware1',
            values: ['rf1:F' => false, 'rf1:T' => true]
        );

        // Step 2: Add reinstallFirmware2 parameter to all firmware1 combinations
        $withFirmware2 = self::withParameter(
            name: 'reinstallFirmware2',
            values: ['rf2:F' => false, 'rf2:T' => true],
            baseCases: $withFirmware1
        );

        // Step 3: Add reinstallFirmware3 parameter to all firmware1+2 combinations
        $withFirmware3 = self::withParameter(
            name: 'reinstallFirmware3',
            values: ['rf3:F' => false, 'rf3:T' => true],
            baseCases: $withFirmware2
        );

        // Step 4: Add reinstallConfig1 parameter to all firmware combinations
        $withConfig1 = self::withParameter(
            name: 'reinstallConfig1',
            values: ['rc1:F' => false, 'rc1:T' => true],
            baseCases: $withFirmware3
        );

        // Step 5: Add reinstallConfig2 parameter to all firmware+config1 combinations
        $withConfig2 = self::withParameter(
            name: 'reinstallConfig2',
            values: ['rc2:F' => false, 'rc2:T' => true],
            baseCases: $withConfig1
        );

        // Step 6: Add reinstallConfig3 parameter to complete all combinations
        $allCombinations = self::withParameter(
            name: 'reinstallConfig3',
            values: ['rc3:F' => false, 'rc3:T' => true],
            baseCases: $withConfig2
        );

        return $allCombinations;
    }

    /**
     * Generates device type combinations with secrets/certificates configurations for PHPUnit data providers.
     *
     * Creates test matrix combining all device type prefixes with 4 possible secrets/certificates configurations.
     * Results in device types × 4 configurations combinations (default: 11 device types × 4 configurations = 44 total combinations).
     *
     * @param array<int, string>|null $deviceTypePrefixes Optional array of device type prefixes. If null, uses getSupportedDeviceTypePrefixes()
     *
     * @return array<string, array<array{deviceTypeName: string, deviceTypePrefix: string, certificates: bool, secrets: bool}>>
     *                                                                                                                          PHPUnit data provider formatted test cases
     *
     * Additional information:
     * - buildDeviceTypesSecretsCertificatesMatrix() For configuration combinations
     * - getSupportedDeviceTypePrefixes() For available device types
     *
     * Basic usage:
     * ```php
     * $result = DeviceCommunicationTestMatrixBuilder::buildDeviceTypesSecretsCertificatesMatrix();
     *
     * $result = DeviceCommunicationTestMatrixBuilder::buildDeviceTypesSecretsCertificatesMatrix(['edge-gateway', 'vcc']);
     *
     * Returns:
     * [
     *     'edge-gateway-none-none' => [
     *         ['deviceTypeName' => 'edge-gateway-none-none', 'deviceTypePrefix' => 'edge-gateway', ...]
     *     ],
     *     'edge-gateway-secrets-certificates' => [
     *         ['deviceTypeName' => 'edge-gateway-secrets-certificates', ...]
     *     ],
     *     ... (more combinations based on provided prefixes)
     * ]
     * ```
     */
    public static function buildDeviceTypesSecretsCertificatesMatrix(?array $deviceTypePrefixes = null): array
    {
        // Get all secrets/certificates combinations (4 combinations)
        $secretsCertificatesCombinations = self::buildSecretsCertificatesMatrix();

        // Use provided device type prefixes or default to all supported ones
        $prefixesToUse = $deviceTypePrefixes ?? self::getSupportedDeviceTypePrefixes();

        // Generate all combinations of device type prefixes with configurations
        $allCombinations = [];
        foreach ($prefixesToUse as $deviceTypePrefix) {
            $combinationsForThisPrefix = self::withParameter(
                name: 'configuration',
                values: $secretsCertificatesCombinations,
                baseCases: [$deviceTypePrefix => ['deviceTypePrefix' => $deviceTypePrefix]]
            );

            // Merge combinations for this prefix into the main array
            $allCombinations = array_merge($allCombinations, $combinationsForThisPrefix);
        }

        // Format for PHPUnit data provider (wrap each case in an array)
        $providerData = [];
        foreach ($allCombinations as $key => $caseData) {
            $providerData[$key] = [[
                'deviceTypeName' => $caseData['deviceTypePrefix'].'-'.$caseData['configuration']['deviceTypeName'],
                'deviceTypePrefix' => $caseData['deviceTypePrefix'],
                'certificates' => $caseData['configuration']['certificates'],
                'secrets' => $caseData['configuration']['secrets'],
            ]];
        }

        return $providerData;
    }

    /**
     * Generates all device type configurations with secrets and certificates combinations.
     *
     * Creates 4 distinct configurations covering all combinations of secrets and certificates:
     * none-none, none-certificates, secrets-none, secrets-certificates.
     *
     * @return array<string, array{secrets: bool, certificates: bool, deviceTypeName: string}>
     *                                                                                         Configuration combinations keyed by combination identifier
     *
     * Basic usage:
     * ```php
     * $combinations = DeviceCommunicationTestMatrixBuilder::buildDeviceTypesSecretsCertificatesMatrix();
     *
     * Returns:
     * [
     *     'none-none' => [
     *         'secrets' => false,
     *         'certificates' => false,
     *         'deviceTypeName' => 'none-none'
     *     ],
     *     'none-certificates' => [
     *         'secrets' => false,
     *         'certificates' => true,
     *         'deviceTypeName' => 'none-certificates'
     *     ],
     *     'secrets-none' => [
     *         'secrets' => true,
     *         'certificates' => false,
     *         'deviceTypeName' => 'secrets-none'
     *     ],
     *     'secrets-certificates' => [
     *         'secrets' => true,
     *         'certificates' => true,
     *         'deviceTypeName' => 'secrets-certificates'
     *     ]
     * ]
     * ```
     */
    public static function buildSecretsCertificatesMatrix(): array
    {
        // Step 1: Start with secrets parameter
        $withSecrets = self::withParameter(
            name: 'secrets',
            values: [
                'none' => false,
                'secrets' => true,
            ]
        );

        // Step 2: Add certificates parameter to all secrets combinations
        $withCertificates = self::withParameter(
            name: 'certificates',
            values: [
                'none' => false,
                'certificates' => true,
            ],
            baseCases: $withSecrets
        );

        // Step 3: Add deviceTypeName based on the combination keys
        $finalConfiguration = [];
        foreach ($withCertificates as $key => $parameters) {
            $finalConfiguration[$key] = array_merge($parameters, [
                'deviceTypeName' => $key,
            ]);
        }

        return $finalConfiguration;
    }

    /**
     * Returns all supported device type prefixes.
     *
     * @return array<int, string> List of device type prefixes
     */
    public static function getSupportedDeviceTypePrefixes(): array
    {
        return [
            'edge-gateway',
            'vcc',
            'tk800',
            'tk600',
            'tk500',
            'tk100',
            'edge-gateway-vcc',
            'tk500v2',
            'sg-gateway',
            'flex-edge',
            'tk500v3',
        ];
    }

    /**
     * Returns supported production device types as a mapping of prefix => display name.
     *
     * @return array<string, string> Map of device type prefixes to human-readable names
     */
    public static function getProductionDeviceTypes(): array
    {
        return [
            'edge-gateway' => 'Edge gateway',
            'vcc' => 'VPN Container Client',
            'tk800' => 'TK800',
            'tk600' => 'TK600',
            'tk500' => 'TK500',
            'tk100' => 'TK100',
            'edge-gateway-vcc' => 'Edge gateway with VPN Container Client',
            'tk500v2' => 'TK500v2',
            'sg-gateway' => 'SG-gateway',
            'flex-edge' => 'Flex edge device',
            'tk500v3' => 'TK500v3',
        ];
    }

    /**
     * Converts device type prefixes into values format for matrix operations.
     *
     * Helper method that transforms device type prefixes into the format expected
     * by matrix builder methods (prefix becomes both key and value).
     *
     * @return array<string, string> Device type prefixes as key-value pairs
     */
    public static function getSupportedDeviceTypePrefixesAsKeyValuePairs(): array
    {
        return self::normalizeValues(self::getSupportedDeviceTypePrefixes());
    }
}
