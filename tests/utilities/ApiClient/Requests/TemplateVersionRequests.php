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

use App\Entity\TemplateVersion;
use App\Enum\TemplateVersionType;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait TemplateVersionRequests
{
    public function getTemplateVersionRequests(): array
    {
        return [
            $this->getTemplateVersionCreateStaging(),
            $this->getTemplateVersionGet(),
            $this->getTemplateVersionSelectStaging(),
            $this->getTemplateVersionSelectProduction(),
            $this->getTemplateVersionDetachStaging(),
            $this->getTemplateVersionDetachProduction(),
            $this->getTemplateVersionEdit(),
            $this->getTemplateVersionDelete(),
            $this->getTemplateVersionList(),
        ];
    }

    public function getTemplateVersionCreateStaging()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/create/staging/{template}',
            uriParameters: [
                'template' => '{template.id}',
            ],
            method: 'POST',
            entityClass: TemplateVersion::class,
            provide: true,
            parameters: [
                'name' => 'Template version {counter}',
                'description' => 'Template version description {counter}',
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
                'type' => TemplateVersionType::STAGING->value,
            ],
        );
    }

    public function getTemplateVersionGet()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: TemplateVersion::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
            ],
        );
    }

    public function getTemplateVersionSelectStaging()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/select/staging/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: TemplateVersion::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'type' => TemplateVersionType::STAGING->value,
            ],
        );
    }

    public function getTemplateVersionSelectProduction()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/select/production/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: TemplateVersion::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'type' => TemplateVersionType::PRODUCTION->value,
            ],
        );
    }

    public function getTemplateVersionDetachStaging()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/detach/staging/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: TemplateVersion::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'type' => TemplateVersionType::STAGING->value,
            ],
        );
    }

    public function getTemplateVersionDetachProduction()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/detach/production/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'GET',
            entityClass: TemplateVersion::class,
            provide: true,
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                Assert::ENTITY_EXISTS,
                'id' => Assert::RESPONSE_VALUE_IS_ID,
                'type' => TemplateVersionType::PRODUCTION->value,
            ],
        );
    }

    public function getTemplateVersionEdit()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'POST',
            entityClass: TemplateVersion::class,
            provide: true,
            parameters: [
                'name' => '{name}',
                'description' => '{description}',
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

    public function getTemplateVersionDelete()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/{id}',
            uriParameters: [
                'id' => '{id}',
            ],
            method: 'DELETE',
            entityClass: TemplateVersion::class,
            asserts: [
                Assert::RESPONSE_204,
                Assert::ENTITY_NOT_EXISTS,
            ],
        );
    }

    public function getTemplateVersionList()
    {
        return new ApiClientRequest(
            uri: '/web/api/templateversion/list',
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
