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

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Areas
{
    public const SUPPORTED_AREAS = [
        // Area 'authenticationData' is a special case. Do not use it without reading this.
        // Used by App\Controller\Api\AuthenticationController to make sure that every area includes App\Model\AuthenticationData::class schema
        // It is needed by /web/api/authentication/* paths defined in config/packages/nelmio_api_doc.yaml
        // Lack of it generates 500 error for VPN documentation when VPN is disabled on configuration level.
        'authenticationData',
        'admin',
        'admin:scep',
        'admin:vpnsecuritysuite',
        'smartems',
        'vpnsecuritysuite',
    ];

    public array $areas = [];

    public function __construct(array $areas = [])
    {
        $unsupportedAreas = array_diff($areas, self::SUPPORTED_AREAS);
        if (count($unsupportedAreas) > 0) {
            throw new \Exception('Unsupported areas has been used: '.implode(', ', $unsupportedAreas));
        }

        $this->areas = $areas;
    }

    public function getAreas(): array
    {
        return $this->areas;
    }

    public function setAreas(array $areas)
    {
        $this->areas = $areas;
    }
}
