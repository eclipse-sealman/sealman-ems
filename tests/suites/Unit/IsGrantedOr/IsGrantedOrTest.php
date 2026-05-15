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

namespace Tests\Suites\Unit\IsGrantedOr;

use App\DataFixtures as ProdFixtures;
use PHPUnit\Framework\Attributes\Group;
use Tests\DataFixtures as TestFixtures;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\ApiClient\ApiClientAssert;

/**
 * Tests App\Attribute\IsGrantedOr attribute on App\Controller\Api\AuthenticatedController.
 */
#[Group('full')]
#[Group('smoke')]
#[Group('Unit')] // To be used in CI parallel tests
class IsGrantedOrTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserFixtures::class,
            ProdFixtures\DeviceAuthenticationFixtures::class,
            TestFixtures\User\DeviceManagementFixtures::class,
        ];
    }

    public function testIsGrantedOrDeny()
    {
        $this->loginApi('router', '123456');

        $this->getApiClient()->request(
            uri: '/web/api/authenticated/change/password',
            asserts: fn () => [
                ApiClientAssert::RESPONSE_403,
            ],
        );
    }

    public function testIsGrantedOrOr()
    {
        $this->loginApi('admin', 'admin');

        $this->getApiClient()->request(
            uri: '/web/api/authenticated/change/password',
            asserts: fn () => [
                // Code 400 in this case means that we have access
                ApiClientAssert::RESPONSE_400,
            ],
        );

        $this->loginApi('deviceManagement', 'deviceManagement');

        $this->getApiClient()->request(
            uri: '/web/api/authenticated/change/password',
            asserts: fn () => [
                // Code 400 in this case means that we have access
                ApiClientAssert::RESPONSE_400,
            ],
        );
    }
}
