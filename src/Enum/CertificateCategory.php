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

enum CertificateCategory: string
{
    case CUSTOM = 'custom';
    case DEVICE_VPN = 'deviceVpn';
    case TECHNICIAN_VPN = 'technicianVpn';
    case DPS = 'dps';
    case EDGE_CA = 'edgeCa';
}
