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

use Carve\ApiBundle\Helper\Arr;

/**
 * Code separated for readability.
 * To be used only with AbstractTestCase class.
 */
trait HttpClientProxyTrait
{
    /**
     * Get response value by given key using Arr::get().
     */
    public function getResponseValue(string $key)
    {
        return Arr::get($this->getResponseContentAsArray(), $key);
    }

    public function getRequest()
    {
        return $this->getHttpClient()->getRequest();
    }

    public function getRequestUrl()
    {
        return $this->getHttpClient()->getRequest()->getPathInfo();
    }

    public function getRequestMethod()
    {
        return $this->getHttpClient()->getRequest()->getMethod();
    }

    public function getResponse()
    {
        return $this->getHttpClient()->getResponse();
    }

    public function getResponseContent(): string
    {
        return $this->getHttpClient()->getResponse()->getContent();
    }

    public function getResponseStatusCode(): int
    {
        return $this->getHttpClient()->getResponse()->getStatusCode();
    }

    public function getResponseContentAsArray(): array
    {
        $jsonContent = \json_decode($this->getResponseContent(), true);

        $this->assertIsArray($jsonContent);

        return $jsonContent;
    }
}
