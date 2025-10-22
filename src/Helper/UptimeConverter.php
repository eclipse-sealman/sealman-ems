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

namespace App\Helper;

class UptimeConverter
{
    public static function convertToSeconds(?string $uptime = null): ?int
    {
        if (null === $uptime) {
            return null;
        }

        $uptimeCleaned = preg_replace('/[^0-9:,]/', '', $uptime);

        $dayExploded = explode(',', $uptimeCleaned);
        if (2 !== count($dayExploded)) {
            return null;
        }

        $timeExploded = explode(':', $dayExploded[1]);
        if (3 !== count($timeExploded)) {
            return null;
        }

        $days = $dayExploded[0];
        $hours = $timeExploded[0];
        $minutes = $timeExploded[1];
        $seconds = $timeExploded[2];

        if (!ctype_digit($days)) {
            return null;
        }

        if (!ctype_digit($hours)) {
            return null;
        }

        if (!ctype_digit($minutes)) {
            return null;
        }

        if (!ctype_digit($seconds)) {
            return null;
        }

        return $days * 24 * 60 * 60 + $hours * 60 * 60 + $minutes * 60 + $seconds;
    }
}
