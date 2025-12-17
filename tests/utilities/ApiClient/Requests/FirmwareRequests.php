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

use App\Entity\Firmware;
use App\Enum\Feature;
use App\Enum\SourceType;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait FirmwareRequests
{
    public function getFirmwareRequests(): array
    {
        return [
            $this->getFirmwareCreate(),
            $this->getFirmwareGet(),
            $this->getFirmwareSourceUploadEdit(),
            $this->getFirmwareSourceExternalUrlEdit(),
            $this->getFirmwareDuplicate(),
            $this->getFirmwareDelete(),
            $this->getFirmwareList(),
        ];
    }

    public function getFirmwareCreate()
    {
        return new ApiClientRequest(
            uri: '/web/api/firmware/create',
            method: 'POST',
            entityClass: Firmware::class,
            provide: true,
            parameters: [
                'deviceType' => '{deviceType.id}',
                'feature' => Feature::PRIMARY,
                'sourceType' => SourceType::EXTERNAL_URL,
                'externalUrl' => 'https://localhost/firmware-{counter}.bin',
                'md5' => '{uuid}',
                'name' => 'Firmware {counter}',
                'version' => 'v1.0.{counter}',
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

    public function getFirmwareGet()
    {
        return new ApiClientRequest(
            uri: '/web/api/firmware/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Firmware::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getFirmwareSourceUploadEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/firmware/{id}/source/upload/edit',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: Firmware::class,
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

    public function getFirmwareSourceExternalUrlEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/firmware/{id}/source/externalurl/edit',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: Firmware::class,
            provide: true,
            parameters: [
                'externalUrl' => '{externalUrl}',
                'md5' => '{uuid}',
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

    public function getFirmwareDuplicate()
    {
        return new ApiClientRequest(
            uri: '/web/api/firmware/{id}/duplicate',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: Firmware::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getFirmwareDelete()
    {
        return new ApiClientRequest(
            uri: '/web/api/firmware/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'DELETE',
            entityClass: Firmware::class,
            asserts: [
                Assert::RESPONSE_204,
                Assert::ENTITY_NOT_EXISTS,
            ],
        );
    }

    public function getFirmwareList()
    {
        return new ApiClientRequest(
            uri: '/web/api/firmware/list',
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
