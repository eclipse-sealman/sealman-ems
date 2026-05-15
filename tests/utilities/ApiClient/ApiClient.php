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
use Doctrine\ORM\EntityRepository;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use function Symfony\Component\String\u;

use Symfony\Component\Uid\Uuid;
use Tests\Utilities\Abstract\AbstractTestCase;
use Tests\Utilities\ApiClient\Requests\AccessTagRequests;
use Tests\Utilities\ApiClient\Requests\AuthenticatedRequests;
use Tests\Utilities\ApiClient\Requests\ConfigRequests;
use Tests\Utilities\ApiClient\Requests\DeviceAuthenticationRequests;
use Tests\Utilities\ApiClient\Requests\DeviceEndpointDeviceRequests;
use Tests\Utilities\ApiClient\Requests\DeviceRequests;
use Tests\Utilities\ApiClient\Requests\DeviceTypeRequests;
use Tests\Utilities\ApiClient\Requests\DocumentationRequests;
use Tests\Utilities\ApiClient\Requests\FirmwareRequests;
use Tests\Utilities\ApiClient\Requests\ImportRequests;
use Tests\Utilities\ApiClient\Requests\LabelRequests;
use Tests\Utilities\ApiClient\Requests\TemplateRequests;
use Tests\Utilities\ApiClient\Requests\TemplateVersionRequests;
use Tests\Utilities\ApiClient\Requests\UserRequests;
use Tests\Utilities\ApiClient\Requests\VpnConnectionRequests;
use Tests\Utilities\ApiClient\Requests\VpnRequests;

/**
 * TODO:
 * - Should $parameters and $asserts be a recursive merge?
 * - non-json case. Is not json case even viable with asserts?
 * - Should we change single brackets in parameters to double brackets? This would avoid issues with JSON encoding and there will be less edge cases. Same change can also be applied to TestFixtureTrait.
 */
class ApiClient
{
    use AuthenticatedRequests;
    use AccessTagRequests;
    use LabelRequests;
    use UserRequests;
    use DeviceAuthenticationRequests;
    use ConfigRequests;
    use FirmwareRequests;
    use TemplateRequests;
    use TemplateVersionRequests;
    use DeviceRequests;
    use DeviceEndpointDeviceRequests;
    use ImportRequests;
    use DeviceTypeRequests;
    use DocumentationRequests;
    use VpnRequests;
    use VpnConnectionRequests;

    use ApiClientAsserts;

    protected ?PropertyAccessorInterface $propertyAccessor = null;
    protected array $counters = [];
    protected array $requests = [];
    protected array $provides = [];

    public function __construct(protected AbstractTestCase $testCase)
    {
        $requests = [];

        $requests = array_merge($requests, $this->getAuthenticatedRequests());
        $requests = array_merge($requests, $this->getAccessTagRequests());
        $requests = array_merge($requests, $this->getLabelRequests());
        $requests = array_merge($requests, $this->getUserRequests());
        $requests = array_merge($requests, $this->getDeviceAuthenticationRequests());
        $requests = array_merge($requests, $this->getConfigRequests());
        $requests = array_merge($requests, $this->getFirmwareRequests());
        $requests = array_merge($requests, $this->getTemplateRequests());
        $requests = array_merge($requests, $this->getTemplateVersionRequests());
        $requests = array_merge($requests, $this->getDeviceRequests());
        $requests = array_merge($requests, $this->getDeviceEndpointDeviceRequests());
        $requests = array_merge($requests, $this->getDeviceTypeRequests());
        $requests = array_merge($requests, $this->getImportRequests());
        $requests = array_merge($requests, $this->getDocumentationRequests());
        $requests = array_merge($requests, $this->getVpnRequests());
        $requests = array_merge($requests, $this->getVpnConnectionRequests());

        $this->requests = $requests;
    }

    public function getRepository(string $class): EntityRepository
    {
        return $this->testCase->getRepository($class);
    }

    public function request(
        string $uri,
        array $uriParameters = [],
        ?string $method = null,
        array|callable $parameters = [],
        array|callable $asserts = [],
        bool $json = true,
        bool $disableApplyProvideOnParameters = false,
    ): void {
        $request = $this->findRequest($uri, $method);
        if (!$request) {
            throw new \Exception('Request not found for URI "'.$uri.'" and method "'.$method.'"');
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
        $request->setResolvedUri($uri);

        if (is_callable($asserts)) {
            $asserts = $asserts($request->asserts);
        } else {
            $asserts = array_merge($request->asserts, $asserts);
        }

        if ($request->entityClass) {
            $this->incrementCounter($request->entityClass);
        }

        $this->testCase->jsonRequest(
            method: $request->method,
            uri: $uri,
            parameters: $parameters,
        );

        foreach ($asserts as $parameterName => $assertDefinition) {
            if (is_iterable($assertDefinition)) {
                foreach ($assertDefinition as $assert) {
                    $this->assert($assert, $parameterName, $parameters, $uriParameters, $request);
                }
            } else {
                $this->assert($assertDefinition, $parameterName, $parameters, $uriParameters, $request);
            }
        }

        if ($request->provide) {
            $this->provide($request->entityClass, $this->testCase->getResponseContentAsArray());
        }
    }

    public function findRequest(string $uri, ?string $method = null): ?ApiClientRequest
    {
        $suspect = null;

        foreach ($this->requests as $request) {
            if ($request->uri === $uri && null === $method) {
                if ($suspect) {
                    throw new \Exception('Multiple requests found for URI "'.$uri.'" and method "'.$method.'". Did you forget to provide method?');
                }

                $suspect = $request;
                continue;
            }

            if ($request->uri === $uri && $request->method === $method) {
                return $request;
            }
        }

        return $suspect;
    }

    protected function applyProvideOnParameters(array $parameters, ?string $entityClass = null): array
    {
        $appliedParameters = [];

        foreach ($parameters as $parameterPath => $parameterValue) {
            $this->getPropertyAccessor()->setValue($appliedParameters, '['.$parameterPath.']', $this->applyProvideOnParameter($parameterValue, $entityClass));
        }

        return $appliedParameters;
    }

    protected function applyProvideOnParameter($value, ?string $entityClass = null)
    {
        if (!is_string($value) || !str_contains($value, '{') || !str_contains($value, '}')) {
            return $value;
        }

        $searches = [];
        $replacements = [];

        $searches[] = '{uuid}';
        $replacements[] = Uuid::v4()->toRfc4122();

        if ($entityClass) {
            $searches[] = '{counter}';
            $replacements[] = $this->getCounter($entityClass);
        }

        // Find all occurrences of variables in the value
        preg_match_all('/\{([^\}]+)\}/', $value, $matches);
        foreach ($matches[1] as $match) {
            $searches[] = '{'.$match.'}';

            $parts = explode('.', $match);
            if (1 === count($parts) && $entityClass) {
                $replacements[] = Arr::get($this->provides, $this->getEntityClassAsPath($entityClass).'.'.$match);
            } else {
                $replacements[] = Arr::get($this->provides, $match);
            }
        }

        return str_replace($searches, $replacements, $value);
    }

    protected function applyVariables($value, array $variables)
    {
        if (!\is_string($value)) {
            return $value;
        }

        $searches = [];
        $replacements = [];

        foreach ($variables as $variableName => $variableValue) {
            $search = '{'.$variableName.'}';
            if ($value === $search) {
                return $variableValue;
            }

            if (!is_string($variableValue) && !is_scalar($variableValue)) {
                continue;
            }

            $searches[] = $search;
            $replacements[] = (string) $variableValue;
        }

        return str_replace($searches, $replacements, $value);
    }

    public function provide(string $entityClass, array $parameters): void
    {
        $this->provides[$this->getEntityClassAsPath($entityClass)] = $parameters;
    }

    public function clear(string $path): void
    {
        unset($this->provides[$path]);
    }

    public function get(?string $path = null, bool $required = false): array
    {
        if (!isset($this->provides[$path]) || null === $path) {
            if ($required) {
                throw new \Exception('Path "'.$path.'" not provided');
            }
        }

        return $this->provides[$path] ?? [];
    }

    public function getRequiredProvidedEntityParameter(string $entityClass, string $parameterName): mixed
    {
        $parameterValue = Arr::get($this->getProvidedEntityClass($entityClass, true), $parameterName);
        if (null === $parameterValue) {
            throw new \Exception("Required parameter '{$parameterName}' not provided for entity '{$entityClass}'");
        }

        return $parameterValue;
    }

    public function getProvidedEntityParameter(string $entityClass, string $parameterName): mixed
    {
        return Arr::get($this->getProvidedEntityClass($entityClass, false), $parameterName);
    }

    public function hasProvidedEntityParameter(string $entityClass, string $parameterName): bool
    {
        $providedEntity = $this->getProvidedEntityClass($entityClass, false);
        if (null === $providedEntity) {
            return false;
        }

        $parameterValue = Arr::get($providedEntity, $parameterName);
        if (null === $parameterValue) {
            return false;
        }

        return true;
    }

    public function getProvidedEntityClass(string $entityClass, bool $required = false): ?array
    {
        return $this->get($this->getEntityClassAsPath($entityClass), $required);
    }

    public function incrementCounter(string $entityClass): void
    {
        $counter = $this->getCounter($entityClass);
        $this->counters[$this->getEntityClassAsPath($entityClass)] = ++$counter;
    }

    public function clearCounter(string $entityClass): void
    {
        $this->counters[$this->getEntityClassAsPath($entityClass)] = 1;
    }

    public function getCounter(string $entityClass): int
    {
        return $this->counters[$this->getEntityClassAsPath($entityClass)] ?? 1;
    }

    private function getEntityClassAsPath(string $entityClass): string
    {
        $reflection = new \ReflectionClass($entityClass);
        $shortName = $reflection->getShortName();
        $camelCase = u($shortName)->camel();

        return (string) $camelCase;
    }

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
