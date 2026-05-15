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

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CustomDataVariablePathValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        // Split path by dots to get individual keys
        $keys = explode('.', $value);

        // Validate each key
        foreach ($keys as $key) {
            if (!$this->isValidKey($key)) {
                $this->context->buildViolation($constraint->messageCustomDataVariablePathInvalid)->addViolation();

                return;
            }
        }
    }

    /**
     * Validates that a single key matches the variable name pattern.
     * Pattern is inspired by HTTP GET/POST parameter naming requirements and JSON key naming requirements.
     * Pattern allows: letters, digits, underscore, and special characters > 127 (for UTF-8)
     * First character must be a letter, underscore, or special character > 127.
     */
    private function isValidKey(string $key): bool
    {
        // Pattern matches valid variable names: must start with letter, underscore, or UTF-8 char
        // and can contain letters, digits, underscores, or UTF-8 chars
        return (bool) preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $key);
    }
}
