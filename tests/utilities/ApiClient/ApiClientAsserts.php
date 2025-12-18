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

namespace Tests\Utilities\ApiClient;

use Carve\ApiBundle\Helper\Arr;
use Doctrine\ORM\Proxy\Proxy;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

trait ApiClientAsserts
{
    public function assert(mixed $assert, mixed $parameterName, array $parameters, array $uriParameters, ApiClientRequest $request)
    {
        if ($assert instanceof ApiClientAssert) {
            $this->assertEnum($assert, $parameterName, $parameters, $uriParameters, $request);
        } else {
            $response = $this->testCase->getResponseContentAsArray();
            $responseValue = Arr::get($response, $parameterName);
            $this->testCase->assertSame($assert, $responseValue, 'Value for key "'.$parameterName.'" in the response is different then expected. Expected "'.(string) $assert.'" but got "'.(string) $responseValue.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
        }
    }

    public function assertEnum(mixed $assert, mixed $parameterName, array $parameters, array $uriParameters, ApiClientRequest $request): void
    {
        switch ($assert) {
            case ApiClientAssert::ENTITY_EXISTS:
                $this->assertEntityExists($assert, $parameters, $request);
                break;
            case ApiClientAssert::ENTITY_NOT_EXISTS:
                $this->assertEntityNotExists($assert, $parameters, $uriParameters, $request);
                break;
            case ApiClientAssert::RESPONSE_VALUE_EQUALS_PARAMETER_VALUE:
                $this->assertResponseValueEqualsParameterValue($assert, $parameterName, $parameters, $request);
                break;
            case ApiClientAssert::RESPONSE_VALUE_IS_ARRAY:
                $this->assertResponseParameterIsType('array', $parameterName, $parameters, $request);
                break;
            case ApiClientAssert::RESPONSE_VALUE_IS_INTEGER:
                $this->assertResponseParameterIsType('integer', $parameterName, $parameters, $request);
                break;
            case ApiClientAssert::RESPONSE_VALUE_IS_ID:
                $this->assertResponseParameterIsId($parameterName, $parameters, $request);
                break;
            case ApiClientAssert::RESPONSE_200:
                $this->assertResponseStatus(200, $request);
                break;
            case ApiClientAssert::RESPONSE_204:
                $this->assertResponseStatus(204, $request);
                break;
            case ApiClientAssert::RESPONSE_400:
                $this->assertResponseStatus(400, $request);
                break;
            case ApiClientAssert::RESPONSE_401:
                $this->assertResponseStatus(401, $request);
                break;
            case ApiClientAssert::RESPONSE_403:
                $this->assertResponseStatus(403, $request);
                break;
            case ApiClientAssert::RESPONSE_404:
                $this->assertResponseStatus(404, $request);
                break;
            case ApiClientAssert::RESPONSE_JSON:
                $this->assertResponseJson($request);
                break;
            case ApiClientAssert::RESPONSE_VALUE_EQUALS_ENTITY_VALUE:
                $this->assertResponseValueEqualsEntityValue($assert, $parameterName, $parameters, $request);
                break;
            case ApiClientAssert::RESPONSE_CONTENT_CONTAINS_VALUE:
                $this->assertResponseContentContainsValue($assert, $parameterName, $parameters, $request);
                break;
            case ApiClientAssert::RESPONSE_CONTENT_EQUALS_VALUE:
                $this->assertResponseContentEqualsValue($assert, $parameterName, $parameters, $request);
                break;
            default:
                throw new \Exception('Unknown assert: '.$assert->value);
        }
    }

    public function assertEntityExists(mixed $assert, array $parameters, ApiClientRequest $request): void
    {
        $response = $this->testCase->getResponseContentAsArray();
        $parameterId = 'id';
        if (!Arr::has($response, $parameterId)) {
            $this->throw('Value for key "'.$parameterId.'" not found in the response', __FUNCTION__, $request);
        }

        $id = Arr::get($response, $parameterId);
        $entity = $this->testCase->getRepository($request->entityClass)->find($id);
        $entityClass = null;

        if (is_object($entity)) {
            // Entity can be a doctrine proxy object, we should treat it as a existing entity
            if ($entity instanceof Proxy) {
                $reflectionClass = new \ReflectionClass($entity);
                $entityClass = $reflectionClass->getParentClass()->getName();
            } else {
                $entityClass = get_class($entity);
            }
        } else {
            $entityClass = gettype($entity);
        }

        $this->testCase->assertSame($request->entityClass, $entityClass, 'Entity of class "'.$request->entityClass.'" with ID = "'.$id.'" should exist. Expected instance of "'.$request->entityClass.'" but got "'.$entityClass.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertEntityNotExists(mixed $assert, array $parameters, array $uriParameters, ApiClientRequest $request): void
    {
        $parameterId = 'id';
        if (!Arr::has($uriParameters, $parameterId)) {
            $this->throw('Value for key "'.$parameterId.'" not found in the $uriParameters', __FUNCTION__, $request);
        }

        $id = Arr::get($uriParameters, $parameterId);
        $this->testCase->getEntityManager()->clear();
        $entity = $this->testCase->getRepository($request->entityClass)->find($id);
        $entityClass = is_object($entity) ? get_class($entity) : gettype($entity);

        $this->testCase->assertEntityNull($entity, 'Entity of class "'.$request->entityClass.'" with ID = "'.$id.'" should not exist. Expected "null" but got "'.$entityClass.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertResponseContentEqualsValue(mixed $assert, mixed $parameterName, array $parameters, ApiClientRequest $request): void
    {
        $actual = $this->testCase->getResponseContent();

        $expected = $this->applyVariables($parameterName, $parameters);
        $expected = $this->applyProvideOnParameter($expected, $request->entityClass);

        $this->testCase->assertSame($expected, $actual, 'Expected response: "'.(string) $expected.'" but got "'.(string) $actual.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertResponseContentContainsValue(mixed $assert, mixed $parameterName, array $parameters, ApiClientRequest $request): void
    {
        $actual = $this->testCase->getResponseContent();

        $expected = $this->applyVariables($parameterName, $parameters);
        $expected = $this->applyProvideOnParameter($expected, $request->entityClass);

        $this->testCase->assertStringContainsString($expected, $actual, 'Expected response to contain: "'.(string) $expected.'" but got "'.(string) $actual.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertResponseValueEqualsParameterValue(mixed $assert, mixed $parameterName, array $parameters, ApiClientRequest $request): void
    {
        $response = $this->testCase->getResponseContentAsArray();
        if (!Arr::has($response, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the response', __FUNCTION__, $request);
        }

        if (!Arr::has($parameters, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the $parameters', __FUNCTION__, $request);
        }

        $actual = Arr::get($response, $parameterName);
        $expected = Arr::get($parameters, $parameterName);
        $this->testCase->assertSame($expected, $actual, 'Value for key "'.$parameterName.'" in the response is different then one in $parameters. Expected "'.(string) $expected.'" but got "'.(string) $actual.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    /**
     * @param string $type Type of variable returned by \gettype()
     */
    public function assertResponseParameterIsType(string $type, mixed $parameterName, array $parameters, ApiClientRequest $request): void
    {
        $response = $this->testCase->getResponseContentAsArray();
        if (!Arr::has($response, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the response', __FUNCTION__, $request);
        }

        $actual = Arr::get($response, $parameterName);
        $actualType = gettype($actual);
        $this->testCase->assertSame($type, $actualType, 'Type of value for key "'.$parameterName.'" in the response is different then expected. Expected "'.(string) $type.'" but got "'.(string) $actualType.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertResponseParameterIsId(mixed $parameterName, array $parameters, ApiClientRequest $request): void
    {
        $response = $this->testCase->getResponseContentAsArray();
        if (!Arr::has($response, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the response', __FUNCTION__, $request);
        }

        $actual = Arr::get($response, $parameterName);
        $actualType = gettype($actual);
        $type = 'integer';
        $this->testCase->assertSame($type, $actualType, 'Type of value for key "'.$parameterName.'" in the response is different then expected. Expected "'.(string) $type.'" but got "'.(string) $actualType.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));

        $isGreaterThanZero = $actual > 0 ? true : false;
        $this->testCase->assertTrue($isGreaterThanZero, 'Value for key "'.$parameterName.'" in the response is not greater than zero. '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertResponseStatus(int $statusCode, ApiClientRequest $request): void
    {
        $this->testCase->assertSame($statusCode, $this->testCase->getResponseStatusCode(), 'Expected "'.$statusCode.'" response status code". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertResponseJson(ApiClientRequest $request): void
    {
        $isValidJson = \json_validate($this->testCase->getResponse()->getContent());
        $this->testCase->assertTrue($isValidJson, 'Expected JSON response. '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    public function assertResponseValueEqualsEntityValue(mixed $assert, mixed $parameterName, array $parameters, ApiClientRequest $request): void
    {
        $response = $this->testCase->getResponseContentAsArray();
        if (!Arr::has($response, $parameterName)) {
            $this->throw('Value for key "'.$parameterName.'" not found in the response', __FUNCTION__, $request);
        }

        $parameterId = 'id';
        if (!Arr::has($response, $parameterId)) {
            $this->throw('Value for key "'.$parameterId.'" not found in the $response', __FUNCTION__, $request);
        }

        $id = Arr::get($response, $parameterId);
        $this->testCase->getEntityManager()->clear();
        $entity = $this->testCase->getRepository($request->entityClass)->find($id);
        if (!$entity instanceof $request->entityClass) {
            $this->throw('Entity of class "'.$request->entityClass.'" with ID = "'.$id.'" not found in the database', __FUNCTION__, $request);
        }

        $actual = $this->getPropertyAccessor()->getValue($entity, $parameterName);
        $expected = Arr::get($response, $parameterName);
        $this->testCase->assertSame($expected, $actual, 'Value for key "'.$parameterName.'" in the response is different then one in the entity of class "'.$request->entityClass.'" with ID = "'.$id.'". Expected "'.(string) $expected.'" but got "'.(string) $actual.'". '.$this->getAssertMessageSuffix(__FUNCTION__, $request));
    }

    protected function throw(string $message, string $function, ?ApiClientRequest $request = null): string
    {
        $parts = [$message];
        $parts[] = $this->getAssertMessageSuffix($function, $request);

        throw new \Exception(implode('. ', $parts));
    }

    protected function getAssertMessageSuffix(string $function, ?ApiClientRequest $request = null): string
    {
        $parts = [];

        if ($request) {
            $parts[] = $this->getRequestAsString($request);
        }

        $parts[] = 'Function "'.$function.'()"';

        return implode('. ', $parts);
    }

    protected function getRequestAsString(ApiClientRequest $request): string
    {
        return 'Request URI "'.($request->getResolvedUri() ?? $request->uri).'" with method "'.$request->method.'"';
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
