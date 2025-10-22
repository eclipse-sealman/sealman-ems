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

namespace tests\HttpClient;

use App\HttpClient\HttpClient;
use App\HttpClient\HttpClientException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @group smoke
 * @group full
 */
class HttpClientTest extends TestCase
{
    public function testMockResponses()
    {
        $exampleResult = 'Example response';
        $timeoutResponse = function () {
            // empty strings are turned into timeouts so that they are easy to test
            yield '';
        };

        $mockHttpClient = new MockHttpClient([
            new MockResponse($exampleResult, ['http_code' => 200]),
            new MockResponse('Invalid JSON', ['http_code' => 200]),
            new MockResponse('', ['http_code' => 401]),
            new MockResponse('', ['http_code' => 403]),
            new MockResponse('', ['http_code' => 500]),
            new MockResponse($timeoutResponse()),
        ]);

        $httpClient = $this->getHttpClient($mockHttpClient);

        $result = $httpClient->request('GET', '/anywhere');
        $this->assertSame($exampleResult, $result);

        $httpClient->request('GET', '/anywhere');

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.unauthorized');
        }

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.forbidden');
        }

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.unexpectedStatusCode');
        }

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.transportException');
        }
    }

    public function testToArrayMockResponses()
    {
        $exampleResult = ['any' => 'thing'];
        $timeoutResponse = function () {
            // empty strings are turned into timeouts so that they are easy to test
            yield '';
        };

        $mockHttpClient = new MockHttpClient([
            new MockResponse(json_encode($exampleResult), ['http_code' => 200]),
            new MockResponse('Invalid JSON', ['http_code' => 200]),
            new MockResponse('', ['http_code' => 401]),
            new MockResponse('', ['http_code' => 403]),
            new MockResponse('', ['http_code' => 500]),
            new MockResponse($timeoutResponse()),
        ]);

        $httpClient = $this->getHttpClient($mockHttpClient, true);

        $result = $httpClient->request('GET', '/anywhere');
        $this->assertSame($exampleResult, $result);

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.decodingException');
        }

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.unauthorized');
        }

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.forbidden');
        }

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.unexpectedStatusCode');
        }

        try {
            $httpClient->request('GET', '/anywhere');
            $this->fail('This part expects that HttpClientException is thrown, instead it finished executing');
        } catch (HttpClientException $exception) {
            $this->assertHttpClientException($exception, 'log.httpClient.transportException');
        }
    }

    private function assertHttpClientException(HttpClientException $exception, string $message): void
    {
        $this->assertSame($message, $exception->getLogMessage(), 'Message "'.$message.'" not found in HttpClientException with message "'.$exception->getLogMessage().'" and message variables '.json_encode($exception->getLogMessageVariables()));
    }

    private static function getHttpClient(HttpClientInterface $mockHttpClient, bool $toArray = false): HttpClient
    {
        return new HttpClient($mockHttpClient, 'Test service', $toArray);
    }
}
