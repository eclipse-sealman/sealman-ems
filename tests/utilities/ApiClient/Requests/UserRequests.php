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

use App\Entity\User;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait UserRequests
{
    public function getUserRequests(): array
    {
        return [
            $this->getUserCreate(),
            $this->getUserGet(),
            $this->getUserEdit(),
            $this->getUserChangePassword(),
            $this->getUserDelete(),
            $this->getUserList(),
        ];
    }

    public function getUserCreate()
    {
        return new ApiClientRequest(
            uri: '/web/api/user/create',
            method: 'POST',
            entityClass: User::class,
            provide: true,
            parameters: [
                'username' => 'Admin {counter}',
                'enabled' => true,
                'roleAdmin' => true,
                'plainPassword' => 'admin{counter}',
                'plainPasswordRepeat' => 'admin{counter}',
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

    public function getUserGet()
    {
        return new ApiClientRequest(
            uri: '/web/api/user/{id}',
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

    public function getUserEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/user/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: User::class,
            provide: true,
            parameters: [
                'username' => '{username}',
                'enabled' => '{enabled}',
                'roleAdmin' => '{roleAdmin}',
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

    public function getUserChangePassword()
    {
        return new ApiClientRequest(
            uri: '/web/api/user/changepassword/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: User::class,
            provide: true,
            parameters: [
                'newPlainPassword' => '{username}',
                'newPlainPasswordRepeat' => '{username}',
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getUserDelete()
    {
        return new ApiClientRequest(
            uri: '/web/api/user/{id}',
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

    public function getUserList()
    {
        return new ApiClientRequest(
            uri: '/web/api/user/list',
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
