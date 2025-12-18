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

namespace Tests\Utilities\Abstract\Trait;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Code separated for readability.
 * To be used only with AbstractTestCase class.
 */
trait RequestProxyTrait
{
    public function get(string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null, bool $changeHistory = true): Crawler
    {
        return $this->request('GET', $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    public function post(string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null, bool $changeHistory = true): Crawler
    {
        return $this->request('POST', $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    public function put(string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null, bool $changeHistory = true): Crawler
    {
        return $this->request('PUT', $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    public function delete(string $uri, array $parameters = [], array $files = [], array $server = [], ?string $content = null, bool $changeHistory = true): Crawler
    {
        return $this->request('DELETE', $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    public function jsonGet(string $uri, array $parameters = [], array $server = [], bool $changeHistory = true): Crawler
    {
        return $this->jsonRequest('GET', $uri, $parameters, $server, $changeHistory);
    }

    public function jsonPost(string $uri, array $parameters = [], array $server = [], bool $changeHistory = true): Crawler
    {
        return $this->jsonRequest('POST', $uri, $parameters, $server, $changeHistory);
    }

    public function jsonPut(string $uri, array $parameters = [], array $server = [], bool $changeHistory = true): Crawler
    {
        return $this->jsonRequest('PUT', $uri, $parameters, $server, $changeHistory);
    }

    public function jsonDelete(string $uri, array $parameters = [], array $server = [], bool $changeHistory = true): Crawler
    {
        return $this->jsonRequest('DELETE', $uri, $parameters, $server, $changeHistory);
    }
}
