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

enum CommunicationProcedureRequirement: string
{
    case HAS_FIRMWARE1 = 'hasFirmware1';
    case HAS_FIRMWARE2 = 'hasFirmware2';
    case HAS_FIRMWARE3 = 'hasFirmware3';
    case HAS_CONFIG1 = 'hasConfig1';
    case HAS_CONFIG2 = 'hasConfig2';
    case HAS_CONFIG3 = 'hasConfig3';
    case HAS_ALWAYS_REINSTALL_CONFIG1 = 'hasAlwaysReinstallConfig1';
    case HAS_ALWAYS_REINSTALL_CONFIG2 = 'hasAlwaysReinstallConfig2';
    case HAS_ALWAYS_REINSTALL_CONFIG3 = 'hasAlwaysReinstallConfig3';
    case HAS_CERTIFICATES = 'hasCertificates';
    case HAS_VPN = 'hasVpn';
    case HAS_ENDPOINT_DEVICES = 'hasEndpointDevices';
    case HAS_TEMPLATES = 'hasTemplates';
    case HAS_MASQUERADE = 'hasMasquerade';
    case HAS_GSM = 'hasGsm';
    case HAS_REQUEST_DIAGNOSE = 'hasRequestDiagnose';
    case HAS_REQUEST_CONFIG = 'hasRequestConfig';
    case HAS_DEVICE_COMMANDS = 'hasDeviceCommands';
    case HAS_VARIABLES = 'hasVariables';
    case HAS_DEVICE_TO_NETWORK_CONNECTION = 'hasDeviceToNetworkConnection';
}
