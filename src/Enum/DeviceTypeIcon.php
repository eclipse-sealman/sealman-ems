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

namespace App\Enum;

enum DeviceTypeIcon: string
{
    // Not what icon should be used. We are not eligable to decide how frontend will be prepared
    case ROUTER = 'router';
    case STAR = 'star';
    case COMPUTER = 'computer';
    case DEVICES = 'devices';
    case CELLTOWER = 'cellTower';
    case LINKOFF = 'linkOff';
    case API = 'api';
    case HUB = 'hub';
    case SETTINGSINPUTANTENNA = 'settingsInputAntenna';
    case CLOUDSYNC = 'cloudSync';
    case DEVICEHUB = 'deviceHub';
    case SEARCH = 'search';
}
