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

namespace Tests\Utilities\ApiClient\Requests;

use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait AuthenticatedRequests
{
    public function getAuthenticatedRequests(): array
    {
        return [
            $this->getChangePasswordEdit(),
        ];
    }

    public function getChangePasswordEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/authenticated/change/password',
            method: 'POST',
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
            ],
        );
    }
}
