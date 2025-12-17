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

use App\Entity\DeviceEndpointDevice;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait DeviceEndpointDeviceRequests
{
    public function getDeviceEndpointDeviceRequests(): array
    {
        return [
            $this->getDeviceEndpointDeviceGet(),
            $this->getDeviceEndpointDeviceEdit(),
            $this->getDeviceEndpointDeviceDelete(),
            $this->getDeviceEndpointDeviceList(),
            $this->getDeviceEndpointDeviceOpenVpnConnection(),
        ];
    }

    public function getDeviceEndpointDeviceGet()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceendpointdevice/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: DeviceEndpointDevice::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getDeviceEndpointDeviceEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceendpointdevice/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: DeviceEndpointDevice::class,
            provide: true,
            parameters: [
                'description' => '{description}',
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getDeviceEndpointDeviceDelete()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceendpointdevice/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'DELETE',
            entityClass: DeviceEndpointDevice::class,
            asserts: [
                Assert::RESPONSE_204,
                Assert::ENTITY_NOT_EXISTS,
            ],
        );
    }

    public function getDeviceEndpointDeviceList()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceendpointdevice/list',
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

    public function getDeviceEndpointDeviceOpenVpnConnection()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceendpointdevice/{id}/open/vpnconnection',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
            ],
        );
    }
}
