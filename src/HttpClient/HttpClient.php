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

namespace App\HttpClient;

use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpClient
{
    /**
     * @param string $service Service used in log message variables
     * @param bool   $toArray Gets the response body decoded as array, typically from a JSON payload
     */
    public function __construct(
        protected HttpClientInterface $httpClient,
        protected string $service,
        protected bool $toArray = false,
    ) {
    }

    public function get(string $url, ?array $query = null, array $options = [], ?bool $toArray = null)
    {
        if (null !== $query && !isset($options['query'])) {
            $options['query'] = $query;
        }

        return $this->request('GET', $url, $options, $toArray);
    }

    public function delete(string $url, ?array $query = null, array $options = [], ?bool $toArray = null)
    {
        if (null !== $query && !isset($options['query'])) {
            $options['query'] = $query;
        }

        return $this->request('DELETE', $url, $options, $toArray);
    }

    public function post(string $url, ?array $body = null, array $options = [], ?bool $toArray = null)
    {
        if (null !== $body && !isset($options['body'])) {
            $options['body'] = $body;
        }

        return $this->request('POST', $url, $options, $toArray);
    }

    public function put(string $url, ?array $body = null, array $options = [], ?bool $toArray = null)
    {
        if (null !== $body && !isset($options['body'])) {
            $options['body'] = $body;
        }

        return $this->request('PUT', $url, $options, $toArray);
    }

    public function patch(string $url, ?array $body = null, array $options = [], ?bool $toArray = null)
    {
        if (null !== $body && !isset($options['body'])) {
            $options['body'] = $body;
        }

        return $this->request('PATCH', $url, $options, $toArray);
    }

    /**
     * @throws HttpClientException
     */
    public function request(string $method, string $url, array $options = [], ?bool $toArray = null): string|array
    {
        // This code avoids throwing HttpExceptionInterface exceptions (300-599) as they are handled here manually

        try {
            $response = $this->httpClient->request($method, $url, $options);
            // $response is asynchronous, calling getStatusCode() will force waiting for entire response
            $statusCode = $response->getStatusCode();

            if (401 === $statusCode) {
                throw new HttpClientException('log.httpClient.unauthorized', ['service' => $this->service, 'url' => $url]);
            }

            if (403 === $statusCode) {
                throw new HttpClientException('log.httpClient.forbidden', ['service' => $this->service, 'url' => $url]);
            }

            if (200 !== $statusCode) {
                $content = $response->getContent(false);
                throw new HttpClientException('log.httpClient.unexpectedStatusCode', ['service' => $this->service, 'url' => $url, 'response' => '' !== $content ?: '(empty response)', 'statusCode' => $statusCode]);
            }

            if (true === $toArray || (null === $toArray && true === $this->toArray)) {
                return $response->toArray(false);
            }

            return $response->getContent(false);
        } catch (DecodingExceptionInterface $exception) {
            $content = $response->getContent(false);
            throw new HttpClientException('log.httpClient.decodingException', ['service' => $this->service, 'url' => $url, 'response' => '' !== $content ?: '(empty response)', 'message' => $exception->getMessage()]);
        } catch (TransportExceptionInterface $exception) {
            throw new HttpClientException('log.httpClient.transportException', ['service' => $this->service, 'url' => $url, 'message' => $exception->getMessage()]);
        }
    }
}
