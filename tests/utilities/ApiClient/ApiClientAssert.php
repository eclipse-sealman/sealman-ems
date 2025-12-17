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

namespace Tests\Utilities\ApiClient;

enum ApiClientAssert: string
{
    case ENTITY_EXISTS = 'assert:entity_exists';
    case ENTITY_NOT_EXISTS = 'assert:entity_not_exists';
    /*
     * Assert that value in the response is the same as value in parameters.
     */
    case RESPONSE_VALUE_EQUALS_PARAMETER_VALUE = 'assert:response_value:equals_parameter_value';
    case RESPONSE_VALUE_IS_ARRAY = 'assert:response_value:is_array';
    case RESPONSE_VALUE_IS_INTEGER = 'assert:response_value:is_integer';
    case RESPONSE_VALUE_IS_ID = 'assert:response_value:is_id';
    /*
     * Assert that response content contains or equals a specific value.
     * ParameterName (array key) is used as specific value and will have parameters applied as variables.
     * Example: 'Router with Serial = {deviceIdentifier} does not exist. Creating new
     */
    case RESPONSE_CONTENT_CONTAINS_VALUE = 'assert:response_content:contains_value';
    case RESPONSE_CONTENT_EQUALS_VALUE = 'assert:response_content:equals_value';
    /*
     * Assert that response http status code is as expected.
     */
    case RESPONSE_200 = 'assert:response_200';
    case RESPONSE_204 = 'assert:response_204';
    case RESPONSE_400 = 'assert:response_400';
    case RESPONSE_401 = 'assert:response_401';
    case RESPONSE_403 = 'assert:response_403';
    case RESPONSE_404 = 'assert:response_404';
    /*
     * Assert that response content is in JSON format.
     */
    case RESPONSE_JSON = 'assert:response_json';
    /*
     * Assert that value in the response is the same as value in entity.
     */
    case RESPONSE_VALUE_EQUALS_ENTITY_VALUE = 'assert:response_value:equals_entity_value';
}
