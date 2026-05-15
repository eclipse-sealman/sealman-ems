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

namespace Tests\Suites\Unit\HttpClient;

use App\Enum\LogLevel;
use App\Exception\ProviderException;
use App\HttpClient\HttpClient;
use App\Provider\ProviderHttpClient;
use App\Trait\LogsCollectorInterface;
use App\Trait\LogsCollectorTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MockLogsCollector implements LogsCollectorInterface
{
    use LogsCollectorTrait;
}

#[Group('full')]
#[Group('smoke')]
#[Group('Unit')] // To be used in CI parallel tests
class ProviderHttpClientTest extends TestCase
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

        $providerHttpClient = $this->getProviderHttpClient($mockHttpClient);

        $result = $providerHttpClient->request('GET', '/anywhere');
        $this->assertSame($exampleResult, $result);

        $providerHttpClient->request('GET', '/anywhere');

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.unauthorized', LogLevel::ERROR);
        }

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.forbidden', LogLevel::ERROR);
        }

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.unexpectedStatusCode', LogLevel::CRITICAL);
        }

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.transportException', LogLevel::ERROR);
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

        $providerHttpClient = $this->getProviderHttpClient($mockHttpClient, true);

        $result = $providerHttpClient->request('GET', '/anywhere');
        $this->assertSame($exampleResult, $result);

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.decodingException', LogLevel::ERROR);
        }

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.unauthorized', LogLevel::ERROR);
        }

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.forbidden', LogLevel::ERROR);
        }

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.unexpectedStatusCode', LogLevel::CRITICAL);
        }

        try {
            $providerHttpClient->request('GET', '/anywhere');
            $this->fail('This part expects that ProviderException is thrown, instead it finished executing');
        } catch (ProviderException $exception) {
            $this->assertProviderException($exception, 'log.httpClient.transportException', LogLevel::ERROR);
        }
    }

    private function assertProviderException(ProviderException $exception, string $message, ?LogLevel $logLevel = null): void
    {
        $messageParts = [];
        $found = false;

        foreach ($exception->getLogs() as $log) {
            $messageParts[] = $log->getMessage().' ('.$log->getLogLevel()->value.') with variables '.json_encode($log->getMessageVariables());

            if (null !== $logLevel && $log->getLogLevel() !== $logLevel) {
                continue;
            }

            if ($log->getMessage() !== $message) {
                continue;
            }

            $found = true;
        }

        $assertMessage = 'Message "'.$message.'" not found in ProviderException';
        if (null !== $logLevel) {
            $assertMessage = 'Message "'.$message.'" with log level "'.$logLevel->value.'" not found in ProviderException';
        }

        $assertMessage .= ' with messages '.implode(', ', $messageParts);

        // Always perform at least one assert (to avoid "This test did not perform any assertions")
        $this->assertSame($found, true, $assertMessage);
    }

    private static function getProviderHttpClient(HttpClientInterface $mockHttpClient, bool $toArray = false): HttpClient
    {
        return new ProviderHttpClient(new MockLogsCollector(), $mockHttpClient, 'Test service', $toArray);
    }
}
