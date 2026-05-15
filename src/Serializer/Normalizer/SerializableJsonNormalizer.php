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

use App\Model\SerializableJson;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * This normalizer supports only App\Model\SerializableJson objects.
 *
 * Supported objects contain JSON as a string.
 *
 * This normalizer keeps empty objects preserved as objects. Example:
 * ```
 * {
 *    "empty": {}
 * }
 * ```
 *
 * would be serialized by symfony serializer v5.4.19 as follows:
 *
 * ```
 * {
 *    "empty": []
 * }
 * ```
 *
 * This normalizer also keeps objects preserved as objects when object keys are "array" like. Example:
 * ```
 * {
 *   "notArray": {
 *     "0": "element0",
 *     "1": "element1"
 *   }
 * }
 * ```
 *
 * would be serialized by symfony serializer v5.4.19 as follows:
 *
 * ```
 * {
 *   "notArray": ["element0", "element1"]
 * }
 * ```
 */
class SerializableJsonNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data, ?string $format = null, array $context = []): \ArrayObject|array|string|int|float|bool|null
    {
        // json_decode with $associative = false returns \stdClass object
        // Putting \stdClass directly in serialized model i.e. EdgeGatewayResponseModel->config (without passing through this normalizer) does not work (empty objects are not preserved as objects, objects are not preserved as objects when object keys are "array" like)
        // On the other hand I have no idea why this solution works (maybe normalizers are executed in different order and \stdClass object is serialized correctly). Remember to verify this when changing symfony serializer version
        // Deprecation present: ArrayObject::__construct(): Using an object as a backing array for ArrayObject is deprecated, as it allows violating class constraints and invariants
        // Do not know how to solve it yet
        return new \ArrayObject(json_decode($data->getJson(), false));
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return $data instanceof SerializableJson;
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
