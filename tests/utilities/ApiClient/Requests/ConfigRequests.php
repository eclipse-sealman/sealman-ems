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

use App\Entity\Config;
use App\Enum\ConfigGenerator;
use App\Enum\Feature;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait ConfigRequests
{
    public function getConfigRequests(): array
    {
        return [
            $this->getConfigCreate(),
            $this->getConfigGet(),
            $this->getConfigEdit(),
            $this->getConfigDuplicate(),
            $this->getConfigDelete(),
            $this->getConfigList(),
        ];
    }

    public function getConfigCreate()
    {
        return new ApiClientRequest(
            uri: '/web/api/config/create',
            method: 'POST',
            entityClass: Config::class,
            provide: true,
            parameters: [
                'name' => 'Config {counter}',
                'generator' => ConfigGenerator::TWIG,
                'content' => 'Config content {counter}',
                'deviceType' => '{deviceType.id}',
                'feature' => Feature::PRIMARY,
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

    public function getConfigGet()
    {
        return new ApiClientRequest(
            uri: '/web/api/config/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Config::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getConfigEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/config/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: Config::class,
            provide: true,
            parameters: [
                'name' => '{name}',
                'generator' => '{generator}',
                'content' => '{content}',
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

    public function getConfigDuplicate()
    {
        return new ApiClientRequest(
            uri: '/web/api/config/{id}/duplicate',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Config::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getConfigDelete()
    {
        return new ApiClientRequest(
            uri: '/web/api/config/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'DELETE',
            entityClass: Config::class,
            asserts: [
                Assert::RESPONSE_204,
                Assert::ENTITY_NOT_EXISTS,
            ],
        );
    }

    public function getConfigList()
    {
        return new ApiClientRequest(
            uri: '/web/api/config/list',
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
