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

namespace Tests\Utilities\DeviceCommunicationApiClient\Requests;

use App\Enum\CommunicationProcedure;
use Tests\Utilities\DeviceCommunicationApiClient\DeviceCommunicationApiClientRequest;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\DeviceCommunicationApiClient\DeviceCommunicationApiClientAssert;

trait SgGatewayRequests
{
    public function getSgGatewayRequests(): array
    {
        return [
            $this->getSgGatewayUnvalidatedResponseRequest(),
            $this->getSgGatewayUnauthorizedResponseRequest(),
            $this->getSgGatewayForbiddenResponseRequest(),
            $this->getSgGatewaySuccessfulResponseRequest(),
            $this->getSgGatewayInitialRequest(),
            $this->getSgGatewayDisabledRequest(),
        ];
    }

    public function getSgGatewayInitialRequest()
    {
        return $this->getSgGatewaySuccessfulResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::INITIAL,
                 variables: [
                    'error' => "{deviceTypeDeviceName} device '{deviceIdentifier}' is disabled.",
                ],
                asserts: [
                    DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                    'error' => DeviceCommunicationApiClientAssert::RESPONSE_VALUE_EQUALS_VARIABLE_VALUE,
                ],
            );
    }

    public function getSgGatewayDisabledRequest()
    {
        return $this->getSgGatewaySuccessfulResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::DISABLED,
                variables: [
                    'error' => "{deviceTypeDeviceName} device '{deviceIdentifier}' is disabled.",
                ],
                asserts: [
                    DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                    'error' => DeviceCommunicationApiClientAssert::RESPONSE_VALUE_EQUALS_VARIABLE_VALUE,
                ],
            );
    }

    public function getSgGatewaySuccessfulResponseRequest()
    {
        return $this->getSgGatewayUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::SUCCESSFUL_RESPONSE,
                asserts: [
                    Assert::RESPONSE_200,
                    Assert::RESPONSE_JSON,
                ],
            );
    }

    public function getSgGatewayUnauthorizedResponseRequest()
    {
        return $this->getSgGatewayUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::UNAUTHORIZED_RESPONSE,
                asserts: [
                    Assert::RESPONSE_401,
                ],
            );
    }

    public function getSgGatewayForbiddenResponseRequest()
    {
        return $this->getSgGatewayUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::FORBIDDEN_RESPONSE,
                asserts: [
                    Assert::RESPONSE_403,
                ],
            );
    }

    public function getSgGatewayUnvalidatedResponseRequest()
    {
        return new DeviceCommunicationApiClientRequest(
            requestType: DeviceCommunicationRequestTypeEnum::UNVALIDATED_RESPONSE,
            communicationProcedure: CommunicationProcedure::SGGATEWAY,
            requestJson: true,
            responseJson: true,
            uri: '{deviceTypeRoutePrefix}/configuration',
            method: 'POST',
            entityClass: DeviceTypeModel::class,
            provide: true,
            uriParameters: [
                'deviceTypeRoutePrefix' => '{deviceTypeRoutePrefix}',
            ],
            parameters: [
                'serialNumber' => '{deviceIdentifier}',
                'firmwareVersion' => '1.0',
                'hardwareVersion' => '2.0',
            ],
        );
    }
}
