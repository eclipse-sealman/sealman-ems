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

use App\Entity\Device;
use App\Enum\VariableType;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait DeviceRequests
{
    public function getDeviceRequests(): array
    {
        return [
            $this->getDeviceCreate(),
            $this->getDeviceGet(),
            $this->getDeviceEnable(),
            $this->getDeviceDisable(),
            $this->getDeviceEdit(),
            $this->getDeviceDelete(),
            $this->getDeviceList(),
            $this->getDeviceGenerateConfigPrimary(),
            $this->getDeviceGenerateConfigSecondary(),
            $this->getDeviceGenerateConfigTertiary(),
            $this->getDeviceBatchVariableAdd(),
            $this->getDeviceOpenVpnConnection(),
        ];
    }

    public function getDeviceCreate()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/create',
            method: 'POST',
            entityClass: Device::class,
            provide: true,
            parameters: [
                'deviceType' => '{deviceType.id}',
                'name' => 'Device {counter}',
                'serialNumber' => 'device-{counter}',
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'name' => [
                    Assert::RESPONSE_VALUE_EQUALS_PARAMETER_VALUE,
                    Assert::RESPONSE_VALUE_EQUALS_ENTITY_VALUE,
                ],
            ],
        );
    }

    public function getDeviceGet()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Device::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getDeviceEnable()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}/enable',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: Device::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'enabled' => true,
            ],
        );
    }

    public function getDeviceDisable()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}/disable',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: Device::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'enabled' => false,
            ],
        );
    }

    public function getDeviceEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: Device::class,
            provide: true,
            parameters: [
                'name' => '{name}',
                'serialNumber' => '{serialNumber}',
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'name' => [
                    Assert::RESPONSE_VALUE_EQUALS_PARAMETER_VALUE,
                    Assert::RESPONSE_VALUE_EQUALS_ENTITY_VALUE,
                ],
            ],
        );
    }

    public function getDeviceDelete()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'DELETE',
            entityClass: Device::class,
            asserts: [
                Assert::RESPONSE_204,
                Assert::ENTITY_NOT_EXISTS,
            ],
        );
    }

    public function getDeviceList()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/list',
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

    public function getDeviceGenerateConfigPrimary()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}/generate/config/primary',
            uriParameters: [
               'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Device::class,
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getDeviceGenerateConfigSecondary()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}/generate/config/secondary',
            uriParameters: [
               'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Device::class,
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getDeviceGenerateConfigTertiary()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}/generate/config/tertiary',
            uriParameters: [
               'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Device::class,
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getDeviceBatchVariableAdd()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/batch/variable/add',
            method: 'POST',
            parameters: [
                'name' => 'variable{counter}',
                'variableType' => VariableType::STRING,
                'variableValue' => '{uuid}',
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
            ],
        );
    }

    public function getDeviceOpenVpnConnection()
    {
        return new ApiClientRequest(
            uri: '/web/api/device/{id}/open/vpnconnection',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
            ],
        );
    }
}
