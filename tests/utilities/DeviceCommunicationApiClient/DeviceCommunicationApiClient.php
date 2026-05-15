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

use App\Enum\AuthenticationMethod;
use App\Enum\CommunicationProcedure;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\ApiClient\ApiClient;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;
use Tests\Utilities\DeviceCommunicationApiClient\Model\DeviceTypeModel;
use Tests\Utilities\DeviceCommunicationApiClient\Requests\EdgeGatewayRequests;
use Tests\Utilities\DeviceCommunicationApiClient\Requests\EdgeGatewayWithVpnContainerClientRequests;
use Tests\Utilities\DeviceCommunicationApiClient\Requests\FlexEdgeRequests;
use Tests\Utilities\DeviceCommunicationApiClient\Requests\RouterRequests;
use Tests\Utilities\DeviceCommunicationApiClient\Requests\SgGatewayRequests;
use Tests\Utilities\DeviceCommunicationApiClient\Requests\VpnContainerClientRequests;

/**
 * DeviceCommunicationApiClient is responsible for handling device communication requests.
 * It extends the ApiClient and uses various traits to handle specific device communication procedures requests.
 */
// TODO add docs what is expected to be provided for apiclient to work correctly
// Test case has to be based on AbstractDeviceCommunicationTestCase
class DeviceCommunicationApiClient extends ApiClient
{
    use RouterRequests;
    use FlexEdgeRequests;
    use SgGatewayRequests;
    use EdgeGatewayRequests;
    use VpnContainerClientRequests;
    use EdgeGatewayWithVpnContainerClientRequests;
    use DeviceCommunicationApiClientAsserts;

    public function __construct(protected AbstractTestCase $testCase)
    {
        parent::__construct($testCase);
        $this->requests = array_merge($this->requests, $this->getRouterOneConfigRequests());
        $this->requests = array_merge($this->requests, $this->getRouterRequests());
        $this->requests = array_merge($this->requests, $this->getRouterDsaRequests());
        $this->requests = array_merge($this->requests, $this->getFlexEdgeRequests());
        $this->requests = array_merge($this->requests, $this->getSgGatewayRequests());
        $this->requests = array_merge($this->requests, $this->getEdgeGatewayRequests());
        $this->requests = array_merge($this->requests, $this->getVpnContainerClientRequests());
        $this->requests = array_merge($this->requests, $this->getEdgeGatewayWithVpnContainerClientRequests());
    }

    public function requestDeviceCommunication(
        DeviceCommunicationRequestTypeEnum $requestType,
        array $uriParameters = [],
        ?string $method = null,
        array|callable $parameters = [],
        array|callable $variables = [],
        array|callable $asserts = [],
        bool $json = true,
        bool $disableApplyProvideOnParameters = false,
    ): void {
        $deviceType = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceType');

        $request = $this->findDeviceCommunicationRequest($requestType, $deviceType->getCommunicationProcedure());
        if (!$request) {
            throw new \Exception('Request not found for requestType "'.$requestType->value.'" and communicationProcedure "'.$deviceType->getCommunicationProcedure()->value.'"');
        }

        if (is_callable($parameters)) {
            $parameters = $parameters($request->parameters);
        } else {
            $parameters = array_merge($request->parameters, $parameters);
        }

        if (!$disableApplyProvideOnParameters) {
            $parameters = $this->applyProvideOnParameters($parameters, $request->entityClass);
        }

        $uriParameters = array_merge($request->uriParameters, $uriParameters);
        $uriParameters = $this->applyProvideOnParameters($uriParameters, $request->entityClass);
        $uri = $this->applyVariables($request->uri, $uriParameters);

        if (str_starts_with($uri, '//')) {
            $uri = '/'.ltrim($uri, '/');
        }

        $request->setResolvedUri($uri);

        if (is_callable($variables)) {
            $variables = $variables($request->variables);
        } else {
            $variables = array_merge($request->variables, $variables);
        }
        $variables = $this->applyProvideOnParameters($variables, $request->entityClass);
        $variables = $this->applyVariables($variables, $parameters);
        $variables = $this->applyVariables($variables, $uriParameters);

        if (is_callable($asserts)) {
            $asserts = $asserts($request->asserts);
        } else {
            $asserts = array_merge($request->asserts, $asserts);
        }

        if ($request->entityClass) {
            $this->incrementCounter($request->entityClass);
        }

        $this->prepareAuthentication();

        if ($request->requestJson) {
            $this->testCase->jsonRequest(
                method: $request->method,
                uri: $uri,
                parameters: $parameters,
            );
        } else {
            $this->testCase->request(
                method: $request->method,
                uri: $uri,
                parameters: $parameters,
            );
        }

        foreach ($asserts as $parameterName => $assertDefinition) {
            if (is_iterable($assertDefinition)) {
                foreach ($assertDefinition as $assert) {
                    $this->assertDeviceCommunication($assert, $parameterName, $parameters, $uriParameters, $variables, $request);
                }
            } else {
                $this->assertDeviceCommunication($assertDefinition, $parameterName, $parameters, $uriParameters, $variables, $request);
            }
        }
    }

    public function findDeviceCommunicationRequest(
        DeviceCommunicationRequestTypeEnum $requestType,
        CommunicationProcedure $communicationProcedure
    ): ?DeviceCommunicationApiClientRequest {
        $suspect = null;

        foreach ($this->requests as $request) {
            if (!$request instanceof DeviceCommunicationApiClientRequest) {
                continue;
            }

            if ($request->requestType === $requestType && $request->communicationProcedure === $communicationProcedure) {
                if ($suspect) {
                    throw new \Exception('Multiple requests found for requestType "'.$requestType->value.'" and communicationProcedure "'.$communicationProcedure->value.'".');
                }

                $suspect = $request;
                continue;
            }
        }

        return $suspect;
    }

    public function prepareAuthentication(): void
    {
        $authenticationMethod = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'deviceTypeCommunicationAuthenticationMethod');

        $username = $this->getProvidedEntityParameter(DeviceTypeModel::class, 'username');
        $password = $this->getProvidedEntityParameter(DeviceTypeModel::class, 'password');

        switch ($authenticationMethod) {
            case AuthenticationMethod::X509:
                $sslCertificate = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'sslCertificate');
                $sslKey = $this->getRequiredProvidedEntityParameter(DeviceTypeModel::class, 'sslKey');
                $this->testCase->loginX509Auth($sslCertificate, $sslKey);
                break;
            case AuthenticationMethod::BASIC:
                $this->testCase->loginBasicAuth($username, $password);
                break;
            case AuthenticationMethod::DIGEST:
                $this->testCase->loginDigestAuth($username, $password);
                break;
            case AuthenticationMethod::NONE:
                $this->testCase->clearAuthorization();
                break;
            default:
                throw new \Exception('Invalid authentication method "'.$authenticationMethod->value.'"');
        }
    }
}
