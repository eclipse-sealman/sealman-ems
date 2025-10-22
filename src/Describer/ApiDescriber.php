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

namespace App\Describer;

use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;

class ApiDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod)
    {
        $this->addResponse401($api, $route, $reflectionMethod);
        $this->addResponse403($api, $route, $reflectionMethod);
        $this->addResponse409($api, $route, $reflectionMethod);
    }

    protected function addResponse401(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod)
    {
        foreach ($this->getOperations($api, $route) as $operation) {
            $existingResponse = $this->findResponse($operation, 401);
            if (null !== $existingResponse) {
                // Do not modify existing response
                continue;
            }

            // #/components/responses/401 is defined in config/packages/nelmio_api_doc.yaml
            $response = Util::createChild($operation, OA\Response::class, ['response' => 401, 'ref' => '#/components/responses/401']);

            if (Generator::UNDEFINED === $operation->responses) {
                $operation->responses = [$response];
            } else {
                $operation->responses[] = $response;
            }
        }
    }

    protected function addResponse403(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod)
    {
        foreach ($this->getOperations($api, $route) as $operation) {
            $existingResponse = $this->findResponse($operation, 403);
            if (null !== $existingResponse) {
                // Do not modify existing response
                continue;
            }

            // #/components/responses/403 is defined in config/packages/nelmio_api_doc.yaml
            $response = Util::createChild($operation, OA\Response::class, ['response' => 403, 'ref' => '#/components/responses/403']);

            if (Generator::UNDEFINED === $operation->responses) {
                $operation->responses = [$response];
            } else {
                $operation->responses[] = $response;
            }
        }
    }

    protected function addResponse409(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod)
    {
        foreach ($this->getOperations($api, $route) as $operation) {
            $existingResponse = $this->findResponse($operation, 409);
            if (null !== $existingResponse) {
                // Do not modify existing response
                continue;
            }

            // #/components/responses/409 is defined in config/packages/nelmio_api_doc.yaml
            $response = Util::createChild($operation, OA\Response::class, ['response' => 409, 'ref' => '#/components/responses/409']);

            if (Generator::UNDEFINED === $operation->responses) {
                $operation->responses = [$response];
            } else {
                $operation->responses[] = $response;
            }
        }
    }

    protected function findResponse(OA\Operation $operation, string|int $response): ?OA\Response
    {
        if (Generator::UNDEFINED === $operation->responses) {
            return null;
        }

        foreach ($operation->responses as $oaResponse) {
            // Use == as $response can be string or int
            if ($oaResponse->response == $response) {
                return $oaResponse;
            }
        }

        return null;
    }
}
