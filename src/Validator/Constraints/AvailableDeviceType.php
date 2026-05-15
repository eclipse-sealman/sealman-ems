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

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class AvailableDeviceType extends Constraint
{
    public string $messageDisabled = 'validation.deviceType.disabled';
    public string $messageNotAvailable = 'validation.deviceType.notAvailable';

    #[HasNamedArguments]
    public function __construct(
        ?string $messageDisabled = null,
        ?string $messageNotAvailable = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->messageDisabled = $messageDisabled ?? $this->messageDisabled;
        $this->messageNotAvailable = $messageNotAvailable ?? $this->messageNotAvailable;
    }
}
