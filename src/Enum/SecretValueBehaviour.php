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

namespace App\Enum;

enum SecretValueBehaviour: string
{
    case NONE = 'none';
    case GENERATE = 'generate';
    case RENEW = 'renew';
    case GENERATE_RENEW = 'generateRenew';

    public static function getRenewEnums(): array
    {
        return [SecretValueBehaviour::RENEW, SecretValueBehaviour::GENERATE_RENEW];
    }

    public static function isRenew($enum): bool
    {
        if (\is_string($enum)) {
            $enum = SecretValueBehaviour::tryFrom($enum);
        }

        if (!$enum instanceof SecretValueBehaviour) {
            return false;
        }

        // Cannot use match with arrays
        if (in_array($enum, self::getRenewEnums())) {
            return true;
        }

        return match ($enum) {
            SecretValueBehaviour::NONE, SecretValueBehaviour::GENERATE => false,
            default => throw new \Exception('Unsupported enum "'.$enum->value.'"'),
        };
    }

    public static function getGenerateEnums(): array
    {
        return [SecretValueBehaviour::GENERATE, SecretValueBehaviour::GENERATE_RENEW];
    }

    public static function isGenerate($enum): bool
    {
        if (\is_string($enum)) {
            $enum = SecretValueBehaviour::tryFrom($enum);
        }

        if (!$enum instanceof SecretValueBehaviour) {
            return false;
        }

        // Cannot use match with arrays
        if (in_array($enum, self::getGenerateEnums())) {
            return true;
        }

        return match ($enum) {
            SecretValueBehaviour::NONE, SecretValueBehaviour::RENEW => false,
            default => throw new \Exception('Unsupported enum "'.$enum->value.'"'),
        };
    }
}
