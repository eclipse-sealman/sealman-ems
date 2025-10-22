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

namespace App\Provider;

use App\Exception\ProviderException;
use App\HttpClient\HttpClient;
use App\HttpClient\HttpClientException;
use App\Trait\LogsCollectorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProviderHttpClient extends HttpClient
{
    protected LogsCollectorInterface $logsCollector;

    /**
     * @param string $service Service used in log message variables
     * @param bool   $toArray Gets the response body decoded as array, typically from a JSON payload
     */
    public function __construct(
        LogsCollectorInterface $logsCollector,
        HttpClientInterface $httpClient,
        string $service,
        bool $toArray = false,
    ) {
        $this->logsCollector = $logsCollector;

        parent::__construct($httpClient, $service, $toArray);
    }

    /**
     * @throws ProviderException
     */
    public function request(string $method, string $url, array $options = [], ?bool $toArray = null): string|array
    {
        try {
            return parent::request($method, $url, $options, $toArray);
        } catch (HttpClientException $exception) {
            if ('log.httpClient.unexpectedStatusCode' === $exception->getLogMessage()) {
                throw new ProviderException($this->logsCollector->addLogCritical($exception->getLogMessage(), $exception->getLogMessageVariables()));
            } else {
                throw new ProviderException($this->logsCollector->addLogError($exception->getLogMessage(), $exception->getLogMessageVariables()));
            }
        }
    }
}
