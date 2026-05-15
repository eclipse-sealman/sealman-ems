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

namespace Tests\Utilities\Provider;

class DeviceTypeProvider
{
    /**
     * Returns names of device types.
     *
     * [
     *  'Edge gateway' => ['Edge gateway']
     * ]
     *
     * TODO Why this does NOT include all device types? What are those device types included?
     *
     * @return array<string, array<string>>
     */
    public static function getNames(): array
    {
        $names = [
            'Edge gateway',
            'TK800',
            'TK500',
            'VPN Container Client',
            'Edge gateway with VPN Container Client',
        ];

        $data = [];
        foreach ($names as $name) {
            $data[$name] = [$name];
        }

        return $data;
    }
}
