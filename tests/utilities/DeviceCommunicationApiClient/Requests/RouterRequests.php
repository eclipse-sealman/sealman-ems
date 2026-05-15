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

trait RouterRequests
{
    public function getRouterOneConfigRequests(): array
    {
        return [
            $this->getRouterUnvalidatedResponseRequest(CommunicationProcedure::ROUTER_ONE_CONFIG),
            $this->getRouterUnauthorizedResponseRequest(CommunicationProcedure::ROUTER_ONE_CONFIG),
            $this->getRouterForbiddenResponseRequest(CommunicationProcedure::ROUTER_ONE_CONFIG),
            $this->getRouterSuccessfulResponseRequest(CommunicationProcedure::ROUTER_ONE_CONFIG),
            $this->getRouterInitialRequest(CommunicationProcedure::ROUTER_ONE_CONFIG),
            $this->getRouterDisabledRequest(CommunicationProcedure::ROUTER_ONE_CONFIG),
        ];
    }

    public function getRouterRequests(): array
    {
        return [
            $this->getRouterUnvalidatedResponseRequest(CommunicationProcedure::ROUTER),
            $this->getRouterUnauthorizedResponseRequest(CommunicationProcedure::ROUTER),
            $this->getRouterForbiddenResponseRequest(CommunicationProcedure::ROUTER),
            $this->getRouterSuccessfulResponseRequest(CommunicationProcedure::ROUTER),
            $this->getRouterInitialRequest(CommunicationProcedure::ROUTER),
            $this->getRouterDisabledRequest(CommunicationProcedure::ROUTER),
        ];
    }

    public function getRouterDsaRequests(): array
    {
        return [
            $this->getRouterUnvalidatedResponseRequest(CommunicationProcedure::ROUTER_DSA),
            $this->getRouterUnauthorizedResponseRequest(CommunicationProcedure::ROUTER_DSA),
            $this->getRouterForbiddenResponseRequest(CommunicationProcedure::ROUTER_DSA),
            $this->getRouterSuccessfulResponseRequest(CommunicationProcedure::ROUTER_DSA),
            $this->getRouterInitialRequest(CommunicationProcedure::ROUTER_DSA),
            $this->getRouterDisabledRequest(CommunicationProcedure::ROUTER_DSA),
            $this->getRouterFirmwareRequest(CommunicationProcedure::ROUTER_DSA),
        ];
    }

    public function getRouterInitialRequest(CommunicationProcedure $communicationProcedure): DeviceCommunicationApiClientRequest
    {
        return $this->getRouterSuccessfulResponseRequest($communicationProcedure)
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::INITIAL,
                asserts: [
                    DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                    'Router with Serial = {deviceIdentifier} does not exist. Creating new router.' => Assert::RESPONSE_CONTENT_EQUALS_VALUE,
                ],
            );
    }

    public function getRouterDisabledRequest(CommunicationProcedure $communicationProcedure): DeviceCommunicationApiClientRequest
    {
        return $this->getRouterSuccessfulResponseRequest($communicationProcedure)
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::DISABLED,
                asserts: [
                    DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS_AND_DISABLED,
                    'Router is disabled and does not have Template.' => Assert::RESPONSE_CONTENT_EQUALS_VALUE,
                ],
            );
    }

    public function getRouterFirmwareRequest(CommunicationProcedure $communicationProcedure): DeviceCommunicationApiClientRequest
    {
        $firmwareUrlExtractionFunction = function (string $responseContent): ?string {
            $matches = [];
            // Match URL="..." pattern in the response content
            $pattern = '/URL="([^"]+)"/';

            if (!preg_match($pattern, $responseContent, $matches)) {
                return null;
            }

            $url = trim($matches[1] ?? '');

            // Return null if URL is empty or just whitespace
            return '' !== $url ? $url : null;
        };

        return $this->getRouterSuccessfulResponseRequest($communicationProcedure)
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::FIRMWARE_RESPONSE,
                variables: [
                    'firmwareUrlExtractionFunction' => $firmwareUrlExtractionFunction,
                ],
                asserts: [
                    DeviceCommunicationApiClientAssert::DEVICE_ENTITY_EXISTS,
                    DeviceCommunicationApiClientAssert::FIRMWARE_URL_IN_RESPONSE,
                ],
            );
    }

    public function getRouterSuccessfulResponseRequest(CommunicationProcedure $communicationProcedure): DeviceCommunicationApiClientRequest
    {
        return $this->getRouterUnvalidatedResponseRequest($communicationProcedure)
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::SUCCESSFUL_RESPONSE,
                asserts: [
                    Assert::RESPONSE_200,
                ],
            );
    }

    public function getRouterUnauthorizedResponseRequest(CommunicationProcedure $communicationProcedure): DeviceCommunicationApiClientRequest
    {
        return $this->getRouterUnvalidatedResponseRequest($communicationProcedure)
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::UNAUTHORIZED_RESPONSE,
                asserts: [
                    Assert::RESPONSE_401,
                ],
            );
    }

    public function getRouterForbiddenResponseRequest(CommunicationProcedure $communicationProcedure): DeviceCommunicationApiClientRequest
    {
        return $this->getRouterUnvalidatedResponseRequest($communicationProcedure)
            ->mergeWith(
                requestType: DeviceCommunicationRequestTypeEnum::FORBIDDEN_RESPONSE,
                asserts: [
                    Assert::RESPONSE_403,
                ],
            );
    }

    public function getRouterUnvalidatedResponseRequest(CommunicationProcedure $communicationProcedure): DeviceCommunicationApiClientRequest
    {
        return new DeviceCommunicationApiClientRequest(
            requestType: DeviceCommunicationRequestTypeEnum::UNVALIDATED_RESPONSE,
            communicationProcedure: $communicationProcedure,
            requestJson: false,
            responseJson: false,
            uri: '{deviceTypeRoutePrefix}/{deviceEndpointSuffix}',
            method: 'POST',
            entityClass: DeviceTypeModel::class,
            provide: true,
            uriParameters: [
                'deviceTypeRoutePrefix' => '{deviceTypeRoutePrefix}',
                'deviceEndpointSuffix' => 'config',
            ],
            parameters: [
                'Serial' => '{deviceIdentifier}',
                'Firmware' => '1.0',
                'hardwareVersion' => '2.0',
            ],
        );
    }
}
