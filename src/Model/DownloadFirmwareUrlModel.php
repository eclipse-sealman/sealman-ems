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
 * Model containing extracted parts of download firmware url - for readability.
 */
class DownloadFirmwareUrlModel
{
    public function __construct(
        private string $deviceHash,
        private string $firmwareSecret,
        private string $deviceTypeSlug,
        private string $firmwareUuid,
        private string $firmwareFilename
    ) {
    }

    public function getDeviceHash(): string
    {
        return $this->deviceHash;
    }

    public function setDeviceHash(string $deviceHash)
    {
        $this->deviceHash = $deviceHash;
    }

    public function getFirmwareUuid(): string
    {
        return $this->firmwareUuid;
    }

    public function setFirmwareUuid(string $firmwareUuid)
    {
        $this->firmwareUuid = $firmwareUuid;
    }

    public function getDeviceTypeSlug(): string
    {
        return $this->deviceTypeSlug;
    }

    public function setDeviceTypeSlug(string $deviceTypeSlug)
    {
        $this->deviceTypeSlug = $deviceTypeSlug;
    }

    public function getFirmwareSecret(): string
    {
        return $this->firmwareSecret;
    }

    public function setFirmwareSecret(string $firmwareSecret)
    {
        $this->firmwareSecret = $firmwareSecret;
    }

    public function getFirmwareFilename(): string
    {
        return $this->firmwareFilename;
    }

    public function setFirmwareFilename(string $firmwareFilename)
    {
        $this->firmwareFilename = $firmwareFilename;
    }
}
