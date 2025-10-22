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

#[\Attribute(\Attribute::TARGET_CLASS)]
class SecretValue extends Constraint
{
    public $messageSecretValueMinimumLengthRequirementFailed = 'validation.secretValue.minimumLengthRequirementFailed';
    public $messageSecretValueDigitRequirementFailed = 'validation.secretValue.digitRequirementFailed';
    public $messageSecretValueUppercaseRequirementFailed = 'validation.secretValue.uppercaseRequirementFailed';
    public $messageSecretValueLowercaseRequirementFailed = 'validation.secretValue.lowercaseRequirementFailed';
    public $messageSecretValueSpecialCharRequirementFailed = 'validation.secretValue.specialCharRequirementFailed';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
