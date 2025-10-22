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

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AvailableDeviceType extends Constraint
{
    public $messageDisabled = 'validation.deviceType.disabled';
    public $messageNotAvailable = 'validation.deviceType.notAvailable';

    #[HasNamedArguments]
    public function __construct(null|string $messageDisabled = null, null|string $messageNotAvailable = null, null|array $groups = null, $payload = null)
    {
        $options = array_filter([
            'messageDisabled' => $messageDisabled ?? $this->messageDisabled,
            'messageNotAvailable' => $messageNotAvailable ?? $this->messageNotAvailable,
        ]);

        parent::__construct($options, $groups, $payload);
    }

    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
