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

namespace Tests\Suites\WebApi\Documentation;

use App\DataFixtures as ProdFixtures;
use PHPUnit\Framework\Attributes\Group;
use Tests\Utilities\WebApi\Documentation\AbstractDocumentationTest;

#[Group('full')]
#[Group('smoke')]
class VpnSecuritySuiteTest extends AbstractDocumentationTest
{
    public static function getFixtureGroups(): array
    {
        return [
            ProdFixtures\ConfigurationFixtures::class,
        ];
    }

    public static function getData(): array
    {
        return [
            ['/web/doc/admin', 200],
            ['/web/doc/admin.yaml', 200],
            ['/web/doc/smartems', 200],
            ['/web/doc/smartems.yaml', 200],
            ['/web/doc/vpnsecuritysuite', 200],
            ['/web/doc/vpnsecuritysuite.yaml', 200],
            ['/web/doc/smartemsvpnsecuritysuite', 200],
            ['/web/doc/smartemsvpnsecuritysuite.yaml', 200],
        ];
    }
}
