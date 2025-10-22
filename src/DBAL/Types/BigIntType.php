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

namespace App\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BigIntType as DoctrineBigIntType;
use Doctrine\DBAL\Types\Type;

/**
 * Type that maps a database BIGINT to a PHP int.
 *
 * We are sure that the architecture is 64-bit so we can safely do that.
 */
class BigIntType extends DoctrineBigIntType
{
    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return null === $value ? null : (int) $value;
    }
}
