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

namespace App\Routing;

use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DeviceCommunicationRouteProvider implements RouteProviderInterface
{
    use EntityManagerTrait;
    use DeviceCommunicationFactoryTrait;

    /**
     * Finds routes that may potentially match the request.
     *
     * This may return a mixed list of class instances, but all routes returned
     * must extend the core symfony route. The classes may also implement
     * RouteObjectInterface to link to a content document.
     *
     * This method may not throw an exception based on implementation specific
     * restrictions on the url. That case is considered a not found - returning
     * an empty array. Exceptions are only used to abort the whole request in
     * case something is seriously broken, like the storage backend being down.
     *
     * Note that implementations may not implement an optimal matching
     * algorithm, simply a reasonable first pass.  That allows for potentially
     * very large route sets to be filtered down to likely candidates, which
     * may then be filtered in memory more completely.
     *
     * @param Request $request A request against which to match
     *
     * @return RouteCollection with all Routes that could potentially match
     *                         $request. Empty collection if nothing can match
     */
    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        return $this->deviceCommunicationFactory->getDeviceTypesRoutes();
    }

    /**
     * Find the route using the provided route name.
     *
     * @param string $name The route name to fetch
     *
     * @throws RouteNotFoundException If there is no route with that name in
     *                                this repository
     */
    public function getRouteByName($name): Route
    {
        $routes = $this->getRoutesByNames([$name]);
        if (0 == count($routes)) {
            throw new RouteNotFoundException();
        }

        return $routes[0];
    }

    /**
     * Find many routes by their names using the provided list of names.
     *
     * Note that this method may not throw an exception if some of the routes
     * are not found or are not actually Route instances. It will just return the
     * list of those Route instances it found.
     *
     * This method exists in order to allow performance optimizations. The
     * simple implementation could be to just repeatedly call
     * $this->getRouteByName() while catching and ignoring eventual exceptions.
     *
     * If $names is null, this method SHOULD return a collection of all routes
     * known to this provider. If there are many routes to be expected, usage of
     * a lazy loading collection is recommended. A provider MAY only return a
     * subset of routes to e.g. support paging or other concepts, but be aware
     * that the DynamicRouter will only call this method once per
     * DynamicRouter::getRouteCollection() call.
     *
     * @param array|null $names The list of names to retrieve, In case of null,
     *                          the provider will determine what routes to return
     *
     * @return Route[] Iterable list with the keys being the names from the
     *                 $names array
     */
    public function getRoutesByNames(?array $names = null): \Traversable|array
    {
        if (!$names) {
            return [];
        }
        $returnRoutes = [];
        $routes = $this->getRouteCollectionForRequest(new Request());
        foreach ($routes->all() as $routeName => $route) {
            foreach ($names as $name) {
                if ($name == $routeName) {
                    $returnRoutes[] = $route;
                }
            }
        }

        return $returnRoutes;
    }
}
