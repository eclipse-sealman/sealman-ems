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

use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\ApiClient\ApiClientRequest;

trait DocumentationRequests
{
    public function getDocumentationRequests(): array
    {
        return [
            $this->getAdminArea(),
            $this->getAdminAreaYaml(),
            $this->getSmartemsArea(),
            $this->getSmartemsAreaYaml(),
            $this->getVpnSecuritySuiteArea(),
            $this->getVpnSecuritySuiteAreaYaml(),
            $this->getSmartemsVpnSecuritySuiteArea(),
            $this->getSmartemsVpnSecuritySuiteAreaYaml(),
        ];
    }

    public function getAdminArea()
    {
        return new ApiClientRequest(
            uri: '/web/doc/admin',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getAdminAreaYaml()
    {
        return new ApiClientRequest(
            uri: '/web/doc/admin.yaml',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getSmartemsArea()
    {
        return new ApiClientRequest(
            uri: '/web/doc/smartems',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getSmartemsAreaYaml()
    {
        return new ApiClientRequest(
            uri: '/web/doc/smartems.yaml',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getVpnSecuritySuiteArea()
    {
        return new ApiClientRequest(
            uri: '/web/doc/vpnsecuritysuite',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getVpnSecuritySuiteAreaYaml()
    {
        return new ApiClientRequest(
            uri: '/web/doc/vpnsecuritysuite.yaml',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getSmartemsVpnSecuritySuiteArea()
    {
        return new ApiClientRequest(
            uri: '/web/doc/smartemsvpnsecuritysuite',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }

    public function getSmartemsVpnSecuritySuiteAreaYaml()
    {
        return new ApiClientRequest(
            uri: '/web/doc/smartemsvpnsecuritysuite.yaml',
            method: 'GET',
            asserts: [
                Assert::RESPONSE_200,
            ],
        );
    }
}
