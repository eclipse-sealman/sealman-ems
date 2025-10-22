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

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class TusX509CheckPrivateKey extends Constraint
{
    public $messageInvalid = 'validation.tusX509CheckPrivateKey.invalid';
    public $propertyPath;

    #[HasNamedArguments]
    public function __construct(null|array $options = null, null|string $propertyPath = null, null|array $groups = null, $payload = null)
    {
        $options = array_filter([
            'propertyPath' => $propertyPath ?? $this->propertyPath,
        ]);

        parent::__construct($options, $groups, $payload);
    }

    public function getRequiredOptions(): array
    {
        return [
            'propertyPath',
        ];
    }
}
