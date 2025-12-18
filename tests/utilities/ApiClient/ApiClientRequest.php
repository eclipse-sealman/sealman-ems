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

namespace Tests\Utilities\ApiClient;

class ApiClientRequest
{
    public ?string $resolvedUri = null;

    public function __construct(
        public string $uri,
        public string $method,
        public array $parameters = [],
        public array $uriParameters = [],
        public array $asserts = [],
        public ?string $entityClass = null,
        public ?bool $provide = null,
    ) {
        if (true === $provide && null === $entityClass) {
            throw new \Exception('Entity class is required when provide is true');
        }
    }

    public function getResolvedUri(): ?string
    {
        return $this->resolvedUri;
    }

    public function setResolvedUri(?string $resolvedUri)
    {
        $this->resolvedUri = $resolvedUri;
    }
}
