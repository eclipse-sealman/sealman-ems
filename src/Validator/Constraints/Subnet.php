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
    public $messageSubnet = 'validation.invalidSubnet';

    public function __construct(
        ?array $options = null,
        ?string $version = null,
        ?int $netmaskMin = null,
        ?int $netmaskMax = null,
        ?string $message = 'validation.invalidCidr',
        ?array $groups = null,
        $payload = null
    ) {
        parent::__construct(
            $options,
            $version,
            $netmaskMin,
            $netmaskMax,
            $message,
            $groups,
            $payload,
        );
    }
}
