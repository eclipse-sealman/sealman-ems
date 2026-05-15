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
use Tests\Utilities\ApiClient\ApiClientAssert as Assert;
use Tests\Utilities\DeviceCommunicationApiClient\DeviceCommunicationApiClientAssert;
use Tests\Utilities\DeviceCommunicationApiClient\DeviceCommunicationApiClientRequest;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;

trait EdgeGatewayWithVpnContainerClientRequests
{
    public function getEdgeGatewayWithVpnContainerClientRequests(): array
    {
        return [
            $this->getEdgeGatewayWithVpnContainerClientUnvalidatedResponseRequest(),
            $this->getEdgeGatewayWithVpnContainerClientUnauthorizedResponseRequest(),
            $this->getEdgeGatewayWithVpnContainerClientForbiddenResponseRequest(),
            $this->getEdgeGatewayWithVpnContainerClientSuccessfulResponseRequest(),
            $this->getEdgeGatewayWithVpnContainerClientInitialRequest(),
            $this->getEdgeGatewayWithVpnContainerClientDisabledRequest(),
        ];
    }

    public function getEdgeGatewayWithVpnContainerClientInitialRequest()
    {
        return $this->getEdgeGatewayWithVpnContainerClientSuccessfulResponseRequest()
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

    public function getEdgeGatewayWithVpnContainerClientDisabledRequest()
    {
        return $this->getEdgeGatewayWithVpnContainerClientSuccessfulResponseRequest()
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

    public function getEdgeGatewayWithVpnContainerClientVccConfigurationDisabledRequest()
    {
        return new DeviceCommunicationApiClientRequest(
            requestType: DeviceCommunicationRequestTypeEnum::DISABLED,
            communicationProcedure: CommunicationProcedure::EDGEGATEWAY_WITH_VPNCONTAINERCLIENT,
            requestJson: false,
            responseJson: true,
            uri: '{deviceTypeRoutePrefix}/configuration/{deviceIdentifier}',
            method: 'GET',
            entityClass: DeviceTypeModel::class,
            provide: true,
            uriParameters: [
                'deviceTypeRoutePrefix' => '{deviceTypeRoutePrefix}',
                'deviceIdentifier' => '{deviceIdentifier}',
            ],
            variables: [
                'error' => "VPN Container Client with identifier = '{deviceIdentifier}' is disabled",
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                'error' => DeviceCommunicationApiClientAssert::RESPONSE_VALUE_EQUALS_VARIABLE_VALUE,
            ],
        );
    }

    public function getEdgeGatewayWithVpnContainerClientSuccessfulResponseRequest()
    {
        return $this->getEdgeGatewayWithVpnContainerClientUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::SUCCESSFUL_RESPONSE,
                asserts: [
                    Assert::RESPONSE_200,
                    Assert::RESPONSE_JSON,
                ],
            );
    }

    public function getEdgeGatewayWithVpnContainerClientUnauthorizedResponseRequest()
    {
        return $this->getEdgeGatewayWithVpnContainerClientUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::UNAUTHORIZED_RESPONSE,
                asserts: [
                    Assert::RESPONSE_401,
                ],
            );
    }

    public function getEdgeGatewayWithVpnContainerClientForbiddenResponseRequest()
    {
        return $this->getEdgeGatewayWithVpnContainerClientUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::FORBIDDEN_RESPONSE,
                asserts: [
                    Assert::RESPONSE_403,
                ],
            );
    }

    public function getEdgeGatewayWithVpnContainerClientUnvalidatedResponseRequest()
    {
        return new DeviceCommunicationApiClientRequest(
            requestType: DeviceCommunicationRequestTypeEnum::UNVALIDATED_RESPONSE,
            communicationProcedure: CommunicationProcedure::EDGEGATEWAY_WITH_VPNCONTAINERCLIENT,
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
                'registrationId' => 'reg{deviceIdentifier}',
                'endorsementKey' => 'ek{deviceIdentifier}',
                'firmwareVersion' => '1.0',
                'hardwareVersion' => '2.0',
            ],
        );
    }
}
