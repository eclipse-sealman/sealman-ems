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

use App\Enum\VariableType;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait ImportRequests
{
    public function getImportRequests(): array
    {
        return [
            $this->getImportFileRowBatchVariableAdd(),
        ];
    }

    public function getImportFileRowBatchVariableAdd()
    {
        return new ApiClientRequest(
            uri: '/web/api/importfilerow/batch/variable/add',
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
}
