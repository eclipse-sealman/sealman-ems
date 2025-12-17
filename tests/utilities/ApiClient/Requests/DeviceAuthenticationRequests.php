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

use App\Entity\DeviceType;
use App\Entity\User;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait DeviceAuthenticationRequests
{
    public function getDeviceAuthenticationRequests(): array
    {
        return [
            $this->getDeviceAuthenticationCreate(),
            $this->getDeviceAuthenticationGet(),
            $this->getDeviceAuthenticationEdit(),
            $this->getDeviceAuthenticationDelete(),
            $this->getDeviceAuthenticationList(),
        ];
    }

    public function getDeviceTypeIds(): array
    {
        $queryBuilder = $this->getRepository(DeviceType::class)->createQueryBuilder('dt');
        $queryBuilder->select('dt.id');

        $result = $queryBuilder->getQuery()->getResult();

        return array_column($result, 'id');
    }

    public function getDeviceAuthenticationCreate()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceauthentication/create',
            method: 'POST',
            entityClass: User::class,
            provide: true,
            parameters: [
                'username' => 'Device authentication {counter}',
                'enabled' => true,
                'password' => 'deviceauthentication{counter}',
                'deviceTypes' => $this->getDeviceTypeIds(),
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'username' => [
                    Assert::RESPONSE_VALUE_EQUALS_PARAMETER_VALUE,
                    Assert::RESPONSE_VALUE_EQUALS_ENTITY_VALUE,
                ],
            ],
        );
    }

    public function getDeviceAuthenticationGet()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceauthentication/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: User::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getDeviceAuthenticationEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceauthentication/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: User::class,
            provide: true,
            parameters: [
                'username' => '{username}',
                'password' => '{password}',
                'deviceTypes' => $this->getDeviceTypeIds(),
                'enabled' => '{enabled}',
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'username' => [
                    Assert::RESPONSE_VALUE_EQUALS_PARAMETER_VALUE,
                    Assert::RESPONSE_VALUE_EQUALS_ENTITY_VALUE,
                ],
            ],
        );
    }

    public function getDeviceAuthenticationDelete()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceauthentication/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'DELETE',
            entityClass: User::class,
            asserts: [
                Assert::RESPONSE_204,
                Assert::ENTITY_NOT_EXISTS,
            ],
        );
    }

    public function getDeviceAuthenticationList()
    {
        return new ApiClientRequest(
            uri: '/web/api/deviceauthentication/list',
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
}
