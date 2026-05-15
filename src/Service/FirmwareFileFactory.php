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

namespace App\Service;

// Factory service to wrap firmware file related operations - to allow easier mocking in tests
class FirmwareFileFactory
{
    // Wrapper method to allow mocking in tests
    public function getFileSize(string $file): int
    {
        return filesize($file);
    }

    // Wrapper method to allow mocking in tests
    public function getFileMd5(string $file): string
    {
        return md5_file($file);
    }
}
