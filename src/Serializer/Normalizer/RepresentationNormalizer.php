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

    /**
     * Return supresses following deprecation message.
     *
     * Method "Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()" might add "array|string|int|float|bool|\ArrayObject|null" as a native return type declaration in the future.
     *
     * @return mixed
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $result = [];

        if (method_exists($object, 'getId')) {
            $result['id'] = $object->getId();
        }

        if (method_exists($object, 'getRepresentation')) {
            $result['representation'] = $object->getRepresentation();
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
