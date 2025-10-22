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

enum UserRole: string
{
    case SHOW = 'show';
    case OPERATE = 'operate';
    case MANAGE = 'manage'; // ?
    case ADMIN = 'admin';
    case DEVICE = 'device'; // only for device users e.g. router, fe, eg etc.
}
