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

// Using class to have access to DeviceTypeSecret object
#[\Attribute(\Attribute::TARGET_CLASS)]
class SecretVariablePrefix extends Constraint
{
    public $messageVariablePrefixUsedInPredefinedVariabled = 'validation.secretVariablePrefix.variablePrefixUsedInPredefinedVariables';
    public $messageVariablePrefixUsedInDeviceTypeSecret = 'validation.secretVariablePrefix.variablePrefixUsedInDeviceTypeSecret';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
