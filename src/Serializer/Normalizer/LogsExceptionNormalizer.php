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

use App\Exception\LogsException;
use Carve\ApiBundle\Serializer\Normalizer\RequestExecutionExceptionNormalizer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

/**
 * Provides normalization for App\Exception\LogsException.
 */
class LogsExceptionNormalizer extends RequestExecutionExceptionNormalizer
{
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (!($data instanceof FlattenException)) {
            return false;
        }

        return LogsException::class === $data->getClass();
    }
}
