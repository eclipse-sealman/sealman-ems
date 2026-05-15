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

trait VpnContainerClientRequests
{
    public function getVpnContainerClientRequests(): array
    {
        return [
            $this->getVpnContainerClientUnvalidatedResponseRequest(),
            $this->getVpnContainerClientUnauthorizedResponseRequest(),
            $this->getVpnContainerClientForbiddenResponseRequest(),
            $this->getVpnContainerClientSuccessfulResponseRequest(),
            $this->getVpnContainerClientInitialRequest(),
            $this->getVpnContainerClientDisabledRequest(),
        ];
    }

    public function getVpnContainerClientInitialRequest()
    {
        return new DeviceCommunicationApiClientRequest(
            requestType: DeviceCommunicationRequestTypeEnum::INITIAL,
            communicationProcedure: CommunicationProcedure::VPNCONTAINERCLIENT,
            requestJson: false,
            responseJson: true,
            uri: '{deviceTypeRoutePrefix}/register',
            method: 'POST',
            entityClass: DeviceTypeModel::class,
            provide: true,
            uriParameters: [
                'deviceTypeRoutePrefix' => '{deviceTypeRoutePrefix}',
            ],
            parameters: [
                'name' => '{deviceIdentifier}',
            ],
            variables: [
                'name' => '{deviceIdentifier}',
            ],
            asserts: [
                Assert::RESPONSE_200,
                Assert::RESPONSE_JSON,
                'name' => DeviceCommunicationApiClientAssert::RESPONSE_VALUE_EQUALS_VARIABLE_VALUE,
                'uuid' => DeviceCommunicationApiClientAssert::RESPONSE_VALUE_IS_NEW_DEVICE_IDENTIFIER,
                DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
            ],
        );
    }

    public function getVpnContainerClientDisabledRequest()
    {
        return $this->getVpnContainerClientSuccessfulResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::DISABLED,
                variables: [
                'error' => "VPN Container Client with identifier = '{deviceIdentifier}' is disabled",
                ],
                asserts: [
                    DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                    'error' => DeviceCommunicationApiClientAssert::RESPONSE_VALUE_EQUALS_VARIABLE_VALUE,
                ],
            );
    }

    public function getVpnContainerClientSuccessfulResponseRequest()
    {
        return $this->getVpnContainerClientUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::SUCCESSFUL_RESPONSE,
                asserts: [
                    Assert::RESPONSE_200,
                    Assert::RESPONSE_JSON,
                ],
            );
    }

    public function getVpnContainerClientUnauthorizedResponseRequest()
    {
        return $this->getVpnContainerClientUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::UNAUTHORIZED_RESPONSE,
                asserts: [
                    Assert::RESPONSE_401,
                ],
            );
    }

    public function getVpnContainerClientForbiddenResponseRequest()
    {
        return $this->getVpnContainerClientUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::FORBIDDEN_RESPONSE,
                asserts: [
                    Assert::RESPONSE_403,
                ],
            );
    }

    public function getVpnContainerClientUnvalidatedResponseRequest()
    {
        return new DeviceCommunicationApiClientRequest(
            requestType: DeviceCommunicationRequestTypeEnum::UNVALIDATED_RESPONSE,
            communicationProcedure: CommunicationProcedure::VPNCONTAINERCLIENT,
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
        );
    }
}
