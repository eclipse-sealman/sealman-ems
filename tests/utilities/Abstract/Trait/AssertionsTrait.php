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

namespace Tests\Utilities\Abstract\Trait;

use App\Exception\ProviderException;
use Carve\ApiBundle\Helper\Arr;

/**
 * Code separated for readability.
 * To be used only with AbstractTestCase class.
 */
trait AssertionsTrait
{
    /**
     * You can use this instead of assertNull to avoid huge debug dumps.
     */
    public function assertEntityNull($entity, ?string $message = null): void
    {
        $message = $message ?? 'Expected null, but got an object "'.(is_object($entity) ? get_class($entity) : 'unknown').'"';
        $this->assertTrue(null === $entity ? true : false, $message);
    }

    public function assertResponseIsSuccessfulJson(string $message = ''): void
    {
        $this->assertResponseIsSuccessful($message);
        $this->assertResponseJson();
    }

    public function assertResponseJson(): void
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $this->assertTrue(\json_validate($response->getContent()), 'Expected JSON response for "'.$this->getRequestUrl().'"');
    }

    public function assertSameEnum(\BackedEnum $expected, \BackedEnum $actual): void
    {
        $this->assertSame($expected->value, $actual->value, 'Enums are not identical.');
    }

    public function assertResponseCode(int $expectedCode, ?string $message = null): void
    {
        if (null === $message) {
            $message = 'Expected '.$expectedCode.' response status code, got '.$this->getResponseStatusCode();
        }

        $this->assertSame($expectedCode, $this->getResponseStatusCode(), $message);
    }

    public function assertResponse200(?string $message = 'Expected 200 response status code')
    {
        $this->assertSame(200, $this->getResponseStatusCode(), $message);
    }

    public function assertResponse204(?string $message = 'Expected 204 response status code')
    {
        $this->assertSame(204, $this->getResponseStatusCode(), $message);
    }

    public function assertResponse400(?string $message = 'Expected 400 response status code')
    {
        $this->assertSame(400, $this->getResponseStatusCode(), $message);
    }

    public function assertArrayValue($array, $expectedValue, string $key, ?string $message = null)
    {
        $value = Arr::get($array, $key);
        $message = $message ?? 'Expected value "'.(string) $expectedValue.'" in key "'.$key.'". Got "'.(string) $value.'"';
        $this->assertSame($expectedValue, $value, $message);
    }

    public function assertResponseContentAsArrayValue($expectedValue, string $key, ?string $message = null)
    {
        $this->assertArrayValue($this->getResponseContentAsArray(), $expectedValue, $key, $message);
    }

    public function assertResponse400ErrorMessage($expectedValue, string $fieldName, ?string $message = null)
    {
        $this->assertResponseContentAsArrayValue($expectedValue, 'errors.children.'.$fieldName.'.errors.0.message');
    }

    public function assertResponse401(?string $message = 'Expected 401 response status code')
    {
        $this->assertSame(401, $this->getResponseStatusCode(), $message);
    }

    public function assertResponse403(?string $message = 'Expected 403 response status code')
    {
        $this->assertSame(403, $this->getResponseStatusCode(), $message);
    }

    public function assertResponse404(?string $message = 'Expected 404 response status code')
    {
        $this->assertSame(404, $this->getResponseStatusCode(), $message);
    }

    public function assertResponse409ErrorMessage(string $expectedMessage)
    {
        $this->assertSame(409, $this->getResponseStatusCode(), 'Expected 409 response status code');
        $content = $this->getResponseContentAsArray();

        if (!isset($content['errors'][0]['message'])) {
            $this->fail('Invalid response structure. Expected key ["errors"][0]["message"] is not set.');
        }

        $this->assertSame($expectedMessage, $content['errors'][0]['message'], 'Invalid error message in 409 response');
    }

    public function assertProviderException(ProviderException $exception, string $message): void
    {
        $messages = [];

        foreach ($exception->getLogs() as $log) {
            $messages[] = $log->getMessage();
        }

        // Always perform at least one assert (to avoid "This test did not perform any assertions")
        $this->assertContains($message, $messages, 'Message "'.$message.'" not found in ProviderException with messages '.implode(', ', $messages));
    }

    public function assertMethodExists(object $object, string $methodName): void
    {
        $this->assertTrue(method_exists($object, $methodName), 'Expected method "'.$methodName.'" does not exists');
    }
}
