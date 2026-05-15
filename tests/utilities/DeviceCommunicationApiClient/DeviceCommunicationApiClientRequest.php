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

use App\Enum\CommunicationProcedure;
use Tests\Utilities\DeviceCommunicationApiClient\Enum\DeviceCommunicationRequestTypeEnum;
use Tests\Utilities\ApiClient\ApiClientRequest;

class DeviceCommunicationApiClientRequest extends ApiClientRequest
{
    public function __construct(
        public DeviceCommunicationRequestTypeEnum $requestType,
        public CommunicationProcedure $communicationProcedure,
        public string $uri,
        public string $method,
        public array $parameters = [],
        public array $uriParameters = [],
        public array $variables = [],
        public array $asserts = [],
        public ?string $entityClass = null,
        public ?bool $provide = null,
        public bool $requestJson = false,
        public bool $responseJson = false,
    ) {
        parent::__construct(
            uri: $uri,
            method: $method,
            parameters: $parameters,
            uriParameters: $uriParameters,
            asserts: $asserts,
            entityClass: $entityClass,
            provide: $provide
        );
    }

    public function copyWith(
        ?DeviceCommunicationRequestTypeEnum $requestType = null,
        ?CommunicationProcedure $communicationProcedure = null,
        ?string $uri = null,
        ?string $method = null,
        ?array $parameters = null,
        ?array $uriParameters = null,
        ?array $variables = null,
        ?array $asserts = null,
        ?string $entityClass = null,
        ?bool $provide = null,
        ?bool $requestJson = null,
        ?bool $responseJson = null,
    ): self {
        return new self(
            requestType: $requestType ?? $this->requestType,
            communicationProcedure: $communicationProcedure ?? $this->communicationProcedure,
            uri: $uri ?? $this->uri,
            method: $method ?? $this->method,
            parameters: $parameters ?? $this->parameters,
            uriParameters: $uriParameters ?? $this->uriParameters,
            variables: $variables ?? $this->variables,
            asserts: $asserts ?? $this->asserts,
            entityClass: $entityClass ?? $this->entityClass,
            provide: $provide ?? $this->provide,
            requestJson: $requestJson ?? $this->requestJson,
            responseJson: $responseJson ?? $this->responseJson,
        );
    }

    public function mergeWith(
    ?DeviceCommunicationRequestTypeEnum $requestType = null,
    ?CommunicationProcedure $communicationProcedure = null,
    ?string $uri = null,
    ?string $method = null,
    array $parameters = [],
    array $uriParameters = [],
    array $variables = [],
    array $asserts = [],
    ?string $entityClass = null,
    ?bool $provide = null,
    ?bool $requestJson = null,
    ?bool $responseJson = null,
): self {
        return new self(
        requestType: $requestType ?? $this->requestType,
        communicationProcedure: $communicationProcedure ?? $this->communicationProcedure,
        uri: $uri ?? $this->uri,
        method: $method ?? $this->method,
        parameters: array_merge($this->parameters, $parameters),
        uriParameters: array_merge($this->uriParameters, $uriParameters),
        variables: array_merge($this->variables, $variables),
        asserts: array_merge($this->asserts, $asserts),
        entityClass: $entityClass ?? $this->entityClass,
        provide: $provide ?? $this->provide,
        requestJson: $requestJson ?? $this->requestJson,
        responseJson: $responseJson ?? $this->responseJson,
    );
    }
}
