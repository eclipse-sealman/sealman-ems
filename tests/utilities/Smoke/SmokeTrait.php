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

namespace Tests\Utilities\Smoke;

trait SmokeTrait
{
    /**
     * Is this class a smoke test class? getProviderNamedData() will filter out static::FULL provider data.
     */
    public static function isSmoke(): bool
    {
        return true;
    }
}
