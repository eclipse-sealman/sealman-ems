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

namespace Tests\Utilities\DeviceCommunicationApiClient;

enum DeviceCommunicationApiClientAssert: string
{
    // ! This is not really an assert, it is used with VpnContainerClientRequests - after registration request it assigns response parameter value to deviceIdentifier provided in deviceType entity.
    case RESPONSE_VALUE_IS_NEW_DEVICE_IDENTIFIER = 'assert:response_value:is_new_device_identifier';
    // ASSERTS
    case RESPONSE_VALUE_EQUALS_VARIABLE_VALUE = 'assert:response_value:equals_variable_value';
    case DEVICE_ENTITY_NOT_EXISTS = 'assertDeviceCommunication:device_entity_not_exists';
    case DEVICE_ENTITY_EXISTS = 'assertDeviceCommunication:device_entity_exists';
    case DEVICE_ENTITY_EXISTS_AND_DISABLED = 'assertDeviceCommunication:device_entity_exists_and_disabled';
    case DEVICE_ENTITY_EXISTS_AND_ENABLED = 'assertDeviceCommunication:device_entity_exists_and_enabled';
    case DEVICE_ENTITY_EXISTS_WITHOUT_DEVICE_SECRETS = 'assertDeviceCommunication:device_entity_exists_without_device_secrets';
    case DEVICE_ENTITY_EXISTS_WITHOUT_DEVICE_CERTIFICATES = 'assertDeviceCommunication:device_entity_exists_without_device_certificates';
    case DEVICE_CONNECTIONS_AMOUNT_COUNT = 'assertDeviceCommunication:device_connections_amount_count';
    case FIRMWARE_URL_IN_RESPONSE = 'assertDeviceCommunication:firmware_url_in_response';
}
