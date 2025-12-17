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

namespace Tests\Utilities\WebApi\Documentation;

use App\Exception\UnsupportedValueException;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\ApiClient\ApiClientAssert;

abstract class AbstractDocumentationTest extends AbstractTestCase
{
    /**
     * Return array of rows with following keys:
     * - 0 => Request URI (string)
     * - 1 => Response status 200 or 404 (int).
     */
    abstract public static function getData(): array;

    /**
     * Optimization:
     * with dataProvider = Time: 01:49.180, Memory: 587.51 MB
     * without dataProvider (directly looping over data to skip database restarts) = Time: 01:40.858, Memory: 378.50 MB.
     *
     * Failed test cases are still readable due to explicit failed request URI.
     */
    public function testDocumentation()
    {
        foreach (static::getData() as $row) {
            $uri = $row[0];
            $statusCode = $row[1];

            switch ($statusCode) {
                case 200:
                    $assert = ApiClientAssert::RESPONSE_200;
                    break;
                case 404:
                    $assert = ApiClientAssert::RESPONSE_404;
                    break;
                default:
                    throw new UnsupportedValueException($statusCode);
            }

            $this->getApiClient()->request(
                uri: $uri,
                asserts: fn () => [
                    $assert,
                ],
            );
        }
    }
}
