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

namespace App\Deny;

use App\Entity\SecretLog;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;

class SecretLogDeny extends AbstractApiObjectDeny
{
    public const SHOW_PREVIOUS = 'showPrevious';
    public const SHOW_UPDATED = 'showUpdated';

    public function showPreviousDeny(SecretLog $secretLog): ?string
    {
        if (!$secretLog->getPreviousSecretValue()) {
            return 'previousSecretValueNotAvailable';
        }

        return null;
    }

    public function showUpdatedDeny(SecretLog $secretLog): ?string
    {
        if (!$secretLog->getUpdatedSecretValue()) {
            return 'updatedSecretValueNotAvailable';
        }

        return null;
    }
}
