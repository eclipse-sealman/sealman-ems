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

use Symfony\Component\Validator\Constraints\Cidr;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Subnet extends Cidr
{
    public string $messageSubnet = 'validation.invalidSubnet';
    public string $message = 'validation.invalidCidr';

    public function __construct(
        ?string $version = null,
        ?int $netmaskMin = null,
        ?int $netmaskMax = null,
        ?string $message = null,
        ?array $groups = null,
        $payload = null,
    ) {
        parent::__construct(
            version: $version,
            netmaskMin: $netmaskMin,
            netmaskMax: $netmaskMax,
            message: $message,
            groups: $groups,
            payload: $payload,
        );
    }
}
