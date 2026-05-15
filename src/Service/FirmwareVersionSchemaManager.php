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

use App\Entity\Firmware;
use App\Enum\FirmwareVersionSchema;
use Composer\Semver\Comparator;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use UnexpectedValueException;

class FirmwareVersionSchemaManager
{
    // EdgeGateway OS versioning regex pattern: Major.Minor.Patch[_preRelease]
    // where preRelease consists of one or more alphanumeric characters
    private const EG_OS_REGEX = '^((0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*))(?:_([a-zA-Z0-9]+))?$';

    public function isVersion1HigherThanVersion2(string $version1, string $version2, FirmwareVersionSchema $schema): bool
    {
        return $this->compareVersions($version1, $version2, $schema, '>');
    }

    public function isVersion1HigherThanOrEqualToVersion2(string $version1, string $version2, FirmwareVersionSchema $schema): bool
    {
        return $this->compareVersions($version1, $version2, $schema, '>=');
    }

    public function compareVersions(string $version1, string $version2, FirmwareVersionSchema $schema, string $operator): bool
    {
        if (!in_array($operator, Constraint::getSupportedOperators(), true)) {
            throw new \InvalidArgumentException("Invalid comparison operator: {$operator}");
        }

        if (false === $this->isVersionValid($version1, $schema)) {
            throw new \InvalidArgumentException("Version '{$version1}' is not valid for schema '{$schema->value}'");
        }

        if (false === $this->isVersionValid($version2, $schema)) {
            throw new \InvalidArgumentException("Version '{$version2}' is not valid for schema '{$schema->value}'");
        }

        // Special handling for EG_OS_SCHEMA can be added here
        if (FirmwareVersionSchema::EG_OS_SCHEMA === $schema) {
            $semanticVersion1 = $this->getEgOsSchemaProductionVersionPart($version1); // Contains major.minor.patch
            $preRelease1 = $this->getEgOsSchemaPreReleaseVersionPart($version1); // Contains preRelease part (without underscore)

            $semanticVersion2 = $this->getEgOsSchemaProductionVersionPart($version2); // Contains major.minor.patch
            $preRelease2 = $this->getEgOsSchemaPreReleaseVersionPart($version2); // Contains preRelease part (without underscore)

            $semanticVersionsEqual = Comparator::compare($semanticVersion1, '==', $semanticVersion2);

            $semanticComparison = Comparator::compare($semanticVersion1, $operator, $semanticVersion2);

            if ('' !== $preRelease1 && '' !== $preRelease2) {
                $preReleaseStringComparison = strcmp($preRelease1, $preRelease2);
            } elseif ('' === $preRelease1 && '' === $preRelease2) {
                $preReleaseStringComparison = 0;
            } elseif ('' === $preRelease1) {
                // version1 is a release, version2 is a pre-release
                $preReleaseStringComparison = 1; // release > pre-release
            } else {
                // version1 is a pre-release, version2 is a release
                $preReleaseStringComparison = -1; // pre-release < release
            }

            switch ($operator) {
                case '>':
                    return $semanticComparison || ($semanticVersionsEqual && $preReleaseStringComparison > 0);
                case '>=':
                    return ($semanticComparison && !$semanticVersionsEqual) || ($semanticVersionsEqual && $preReleaseStringComparison >= 0);
                case '<':
                    return $semanticComparison || ($semanticVersionsEqual && $preReleaseStringComparison < 0);
                case '<=':
                    return ($semanticComparison && !$semanticVersionsEqual) || ($semanticVersionsEqual && $preReleaseStringComparison <= 0);
                case '==':
                    return $semanticVersionsEqual && 0 === $preReleaseStringComparison;
                case '!=':
                    return !$semanticVersionsEqual || 0 !== $preReleaseStringComparison;

                default:
                    throw new \InvalidArgumentException("Unsupported operator '{$operator}' for EG_OS_SCHEMA comparison.");
            }
        }

        // Handle other schemas

        // Normalize versions by removing prefixes for comparison
        $normalizedVersion1 = $this->normalizeVersion($version1, $schema);
        $normalizedVersion2 = $this->normalizeVersion($version2, $schema);

        return Comparator::compare($normalizedVersion1, $operator, $normalizedVersion2);
    }

    /**
     * Validate firmware version against schema.
     */
    public function isVersionValid(string $version, FirmwareVersionSchema $schema): bool
    {
        switch ($schema) {
            case FirmwareVersionSchema::ANY_SCHEMA:
                return true; // Any version is valid
            case FirmwareVersionSchema::EG_OS_SCHEMA:
                return $this->validateEgOsSchema($version);
            case FirmwareVersionSchema::V_SEMANTIC_VERSIONING:
                if (!str_starts_with($version, 'v')) {
                    return false; // Must start with 'v'
                }
                // no break here, continue to semantic versioning validation
            case FirmwareVersionSchema::SEMANTIC_VERSIONING:
                return $this->validateSemanticVersioning($this->normalizeVersion($version, $schema));
            default:
                throw new \InvalidArgumentException("Unknown firmware version schema: {$schema->value}");
        }
    }

    /**
     * Sort firmwares according to version schema.
     *
     * @param array<Firmware> $firmwares
     *
     * @return array<Firmware>
     */
    public function sortFirmwares(array $firmwares, FirmwareVersionSchema $firmwareVersionSchema): array
    {
        usort($firmwares, function (Firmware $firmware1, Firmware $firmware2) use ($firmwareVersionSchema) {
            $version1 = $firmware1->getVersion();
            $version2 = $firmware2->getVersion();

            if ($this->isVersion1HigherThanVersion2($version1, $version2, $firmwareVersionSchema)) {
                return -1;
            }

            if ($this->isVersion1HigherThanVersion2($version2, $version1, $firmwareVersionSchema)) {
                return 1;
            }

            return 0;
        });

        return $firmwares;
    }

    /**
     * Validate version according to EdgeGateway OS versioning.
     * According to EdgeGateway OS versioning: Major.Minor.Patch[_preRelease] with optional pre-release tag (preRelease consists of alphanumeric characters).
     */
    private function validateEgOsSchema(string $version): bool
    {
        // Validation executed using exception when separating parts
        try {
            // Separate major.minor.patch from preRelease
            $semanticVersion = $this->getEgOsSchemaProductionVersionPart($version); // Contains major.minor.patch
            $preRelease = $this->getEgOsSchemaPreReleaseVersionPart($version); // Contains preRelease part (without underscore)
        } catch (\InvalidArgumentException) {
            return false; // Invalid version format
        }

        try {
            $parser = new VersionParser();
            $parser->normalize($semanticVersion);
        } catch (UnexpectedValueException) {
            return false; // Invalid version format
        }

        return true; // Valid version format
    }

    private function getEgOsSchemaProductionVersionPart(string $version): string
    {
        // Extract the major.minor.patch part from the version string
        if (preg_match('/'.self::EG_OS_REGEX.'/', $version, $matches)) {
            return $matches[1]; // Return major.minor.patch
        }

        throw new \InvalidArgumentException("Version '{$version}' is not valid for schema 'EG_OS_SCHEMA'");
    }

    private function getEgOsSchemaPreReleaseVersionPart(string $version): string
    {
        // Extract the preRelease part from the version string
        if (preg_match('/'.self::EG_OS_REGEX.'/', $version, $matches)) {
            return $matches[5] ?? ''; // Return preRelease or empty string
        }

        throw new \InvalidArgumentException("Version '{$version}' is not valid for schema 'EG_OS_SCHEMA'");
    }

    /**
     * Validate version according to semantic versioning.
     * According to semantic versioning 2.0.0: https://semver.org/ without support of pre-release and build metadata.
     */
    private function validateSemanticVersioning(string $version): bool
    {
        // Semantic versioning regex pattern
        // Copied from https://semver.org/#is-there-a-suggested-regular-expression-regex-to-check-a-semver-string
        $regex = '^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$';

        // Test version against regex pattern
        if (!preg_match('/'.$regex.'/', $version)) {
            return false; // Version doesn't match semantic versioning pattern
        }

        try {
            $parser = new VersionParser();
            $parser->normalize($version);
        } catch (UnexpectedValueException) {
            return false; // Invalid version format
        }

        return true; // Valid version format
    }

    /**
     * Normalize version string for comparison by removing schema-specific prefixes.
     * Always call after validating the version with isVersionValid().
     */
    private function normalizeVersion(string $version, FirmwareVersionSchema $schema): string
    {
        return match ($schema) {
            FirmwareVersionSchema::V_SEMANTIC_VERSIONING => substr($version, 1), // Its private method and is always used after validation and thus we are sure that 'v' is first character
            default => $version,
        };
    }
}
