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

trait FlexEdgeRequests
{
    public function getFlexEdgeRequests(): array
    {
        return [
            $this->getFlexEdgeUnvalidatedResponseRequest(),
            $this->getFlexEdgeUnauthorizedResponseRequest(),
            $this->getFlexEdgeForbiddenResponseRequest(),
            $this->getFlexEdgeSuccessfulResponseRequest(),
            $this->getFlexEdgeInitialRequest(),
            $this->getFlexEdgeDisabledRequest(),
        ];
    }

    public function getFlexEdgeInitialRequest()
    {
        return $this->getFlexEdgeSuccessfulResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::INITIAL,
                asserts: [
                    DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                    'ERROR: Flex edge device is disabled' => Assert::RESPONSE_CONTENT_EQUALS_VALUE,
                ],
            );
    }

    public function getFlexEdgeDisabledRequest()
    {
        return $this->getFlexEdgeSuccessfulResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::DISABLED,
                variables: [
                    'error' => "Flex edge device '{deviceIdentifier}' is disabled.",
                ],
                asserts: [
                    DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                    'ERROR: Flex edge device is disabled' => Assert::RESPONSE_CONTENT_EQUALS_VALUE,
                ],
            );
    }

    public function getFlexEdgeSuccessfulResponseRequest()
    {
        return $this->getFlexEdgeUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::SUCCESSFUL_RESPONSE,
                asserts: [
                    Assert::RESPONSE_200,
                ],
            );
    }

    public function getFlexEdgeUnauthorizedResponseRequest()
    {
        return $this->getFlexEdgeUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::UNAUTHORIZED_RESPONSE,
                asserts: [
                    Assert::RESPONSE_401,
                ],
            );
    }

    public function getFlexEdgeForbiddenResponseRequest()
    {
        return $this->getFlexEdgeUnvalidatedResponseRequest()
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::FORBIDDEN_RESPONSE,
                asserts: [
                    Assert::RESPONSE_403,
                ],
            );
    }

    public function getFlexEdgeUnvalidatedResponseRequest()
    {
        return new DeviceCommunicationApiClientRequest(
            requestType: DeviceCommunicationRequestTypeEnum::UNVALIDATED_RESPONSE,
            communicationProcedure: CommunicationProcedure::FLEXEDGE,
            uri: '{deviceTypeRoutePrefix}/jbm_mgmt/update_status.php',
            requestJson: false,
            responseJson: false,
            method: 'POST',
            entityClass: DeviceTypeModel::class,
            provide: true,
            uriParameters: [
                'deviceTypeRoutePrefix' => '{deviceTypeRoutePrefix}',
            ],
            parameters: [
                'sn' => '{deviceIdentifier}',
                'ver' => '1.0',
                'mn' => 'DA50',
            ],
        );
    }
}
