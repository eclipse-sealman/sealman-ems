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

use App\Entity\VpnConnection;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait VpnConnectionRequests
{
    public function getVpnConnectionRequests(): array
    {
        return [
            $this->getVpnConnectionList(),
            $this->getVpnConnectionClose(),
        ];
    }

    public function getVpnConnectionList()
    {
        return new ApiClientRequest(
            uri: '/web/api/vpnconnection/list',
            method: 'POST',
            parameters: [
                'page' => 1,
                'rowsPerPage' => 10,
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'results' => Assert::RESPONSE_VALUE_IS_ARRAY,
                'rowCount' => Assert::RESPONSE_VALUE_IS_INTEGER,
            ],
        );
    }

    public function getVpnConnectionClose()
    {
        return new ApiClientRequest(
            uri: '/web/api/vpnconnection/{id}/close/vpnconnection',
            method: 'GET',
            entityClass: VpnConnection::class,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_NOT_EXISTS,
            ],
        );
    }
}
