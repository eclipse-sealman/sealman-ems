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

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RepresentationNormalizer implements NormalizerInterface
{
    public const ENABLED = 'enabled';

    public function normalize(mixed $data, ?string $format = null, array $context = []): \ArrayObject|array|string|int|float|bool|null
    {
        if (null === $data) {
            return [];
        }

        if (!is_object($data)) {
            return [];
        }

        $result = [];

        if (method_exists($data, 'getId')) {
            $result['id'] = $data->getId();
        }

        if (method_exists($data, 'getRepresentation')) {
            $result['representation'] = $data->getRepresentation();
        }

        return $result;
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        $enabled = $context[self::ENABLED] ?? null;
        if (true !== $enabled) {
            return false;
        }

        return true;
    }

    public function getSupportedTypes(?string $format): array
    {
        // In Symfony 5.4 results where not cached by default. Adjust when needed.
        return [
            'object' => false,
            '*' => false,
        ];
    }
}
