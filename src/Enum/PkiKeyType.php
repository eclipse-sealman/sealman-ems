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

enum PkiKeyType: string
{
    case RSA2048 = 'RSA2048';
    case RSA4096 = 'RSA4096';
    case EC_P_521 = 'EC_P_521';
    case ED448 = 'ED448';
    case ED25519 = 'ED25519';
}
