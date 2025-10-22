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

namespace App\Model;

/**
 * Read more about use case of this class in App\Serializer\Normalizer\SerializableJsonNormalizer.
 */
class SerializableJson
{
    /**
     * JSON as a string.
     */
    private ?string $json = null;

    public function __construct(?string $json)
    {
        $this->json = $json;
    }

    public function getJson(): ?string
    {
        return $this->json;
    }

    public function setJson(?string $json)
    {
        $this->json = $json;
    }
}
