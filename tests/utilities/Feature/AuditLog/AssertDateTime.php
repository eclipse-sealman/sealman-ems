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

namespace Tests\Utilities\Feature\AuditLog;

/**
 * Class used by Tests\Utilities\Feature\AuditLog\AssertAuditLogTrait that allows a DateTime to have leeway (i.e. 1 second difference).
 * This allow to avoid false negatives when testing DateTime that represents 'now'.
 */
class AssertDateTime
{
    public function __construct(
        private \DateTime $dateAt,
        /**
         * Leeway in seconds.
         */
        private int $leeway = 1
    ) {
    }

    public function getAcceptedDates(): array
    {
        $acceptedDates = [];

        for ($i = -$this->leeway; $i <= $this->leeway; ++$i) {
            $acceptedDates[] = (clone $this->dateAt)->modify($i.' seconds');
        }

        return $acceptedDates;
    }

    public function getAcceptedDatesFormatted(string $format): array
    {
        return array_map(
            fn (\DateTime $date) => $date->format($format),
            $this->getAcceptedDates()
        );
    }
}
