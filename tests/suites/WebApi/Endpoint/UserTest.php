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

namespace Tests\Suites\WebApi\Endpoint;

use App\DataFixtures as ProdFixtures;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;

#[Group('full')]
#[Group('smoke')]
#[Group('WebApi')] // To be used in CI parallel tests
class UserTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserFixtures::class,
            TestFixtures\Configuration\DisablePasswordRequirementsFixtures::class,
        ];
    }

    public function testCrud()
    {
        $this->loginApi('admin', 'admin');

        $this->getApiClient()->request(
            uri: '/web/api/user/create',
        );
        $this->getApiClient()->request(
            uri: '/web/api/user/{id}',
            method: 'GET',
        );
        $this->getApiClient()->request(
            uri: '/web/api/user/list',
        );
        $this->getApiClient()->request(
            uri: '/web/api/user/{id}',
            method: 'POST',
            parameters: [
                'username' => 'Admin {uuid}',
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/user/{id}',
            method: 'DELETE',
        );
    }

    public function testChangePassword()
    {
        $this->loginApi('admin', 'admin');

        $this->getApiClient()->request(
            uri: '/web/api/user/create',
            parameters: [
                'username' => 'admin1',
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/user/changepassword/{id}',
            parameters: [
                'newPlainPassword' => 'newPassword',
                'newPlainPasswordRepeat' => 'newPassword',
            ],
        );

        $this->loginApi('admin1', 'newPassword');
    }
}
