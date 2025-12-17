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

use App\Entity\Label;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait LabelRequests
{
    public function getLabelRequests(): array
    {
        return [
            $this->getLabelCreate(),
            $this->getLabelGet(),
            $this->getLabelEdit(),
            $this->getLabelDelete(),
            $this->getLabelList(),
        ];
    }

    public function getLabelCreate()
    {
        return new ApiClientRequest(
            uri: '/web/api/label/create',
            method: 'POST',
            entityClass: Label::class,
            provide: true,
            parameters: [
                'name' => 'Label {counter}',
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

    public function getLabelGet()
    {
        return new ApiClientRequest(
            uri: '/web/api/label/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Label::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getLabelEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/label/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: Label::class,
            provide: true,
            parameters: [
                'name' => '{name}',
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

    public function getLabelDelete()
    {
        return new ApiClientRequest(
            uri: '/web/api/label/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'DELETE',
            entityClass: Label::class,
            asserts: [
                Assert::RESPONSE_204,
                Assert::ENTITY_NOT_EXISTS,
            ],
        );
    }

    public function getLabelList()
    {
        return new ApiClientRequest(
            uri: '/web/api/label/list',
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
