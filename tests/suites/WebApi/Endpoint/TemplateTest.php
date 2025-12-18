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
use App\Entity\DeviceType;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\Abstract\AbstractTestCase;

#[Group('full')]
#[Group('smoke')]
class TemplateTest extends AbstractTestCase
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
            ProdFixtures\UserFixtures::class,
            ProdFixtures\DeviceTypeFixtures::class,
        ];
    }

    public function testCrud()
    {
        $this->loginApi('admin', 'admin');

        $deviceType = $this->getRepository(DeviceType::class)->findOneBy(['name' => 'TK800']);
        $this->getApiClient()->provide(DeviceType::class, ['id' => $deviceType->getId()]);

        $this->getApiClient()->request(
            uri: '/web/api/template/create',
        );
        $this->getApiClient()->request(
            uri: '/web/api/template/{id}',
            method: 'GET',
        );
        $this->getApiClient()->request(
            uri: '/web/api/template/list',
        );
        $this->getApiClient()->request(
            uri: '/web/api/template/{id}',
            method: 'POST',
            parameters: [
                'name' => 'Template {uuid}',
            ],
        );
        $this->getApiClient()->request(
            uri: '/web/api/template/{id}',
            method: 'DELETE',
        );
    }
}
