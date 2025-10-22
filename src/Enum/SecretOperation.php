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

enum SecretOperation: string
{
    case COMMUNICATION_SHOW = 'communicationShow';
    case COMMUNICATION_RENEW = 'communicationRenew';
    case USER_SHOW = 'userShow';
    case USER_SHOW_PREVIOUS_LOG = 'userShowPreviousLog';
    case USER_SHOW_UPDATED_LOG = 'userShowUpdatedLog';
    case USER_SHOW_COMMUNICATION_LOG = 'userShowCommunicationLog';
    case USER_SHOW_CONFIG_LOG = 'userShowConfigLog';
    case USER_SHOW_DIAGNOSE_LOG = 'userShowDiagnoseLog';
    case USER_CLEAR = 'userClear';
    case USER_EDIT = 'userEdit';
    case USER_CREATE = 'userCreate';
}
