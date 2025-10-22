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

use App\Attribute\Areas;
use App\Service\Helper\FeatureManagerTrait;
use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Util\ControllerReflector;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class NelmioRouteCollectionBuilder
{
    use FeatureManagerTrait;

    /** @var Reader */
    private $annotationReader;

    /** @var ControllerReflector */
    private $controllerReflector;

    /** @var string */
    private $area;

    public function __construct(Reader $annotationReader, ControllerReflector $controllerReflector)
    {
        $this->annotationReader = $annotationReader;
        $this->controllerReflector = $controllerReflector;
    }

    public function filter(RouteCollection $routes, string $area): RouteCollection
    {
        $this->area = $area;

        $filteredRoutes = new RouteCollection();

        foreach ($routes->all() as $name => $route) {
            if ($this->matchPath($route) && $this->matchAreas($route)) {
                $filteredRoutes->add($name, $route);
            }
        }

        return $filteredRoutes;
    }

    private function matchPath(Route $route): bool
    {
        $pathPattern = '^/web/api';
        if (preg_match('{'.$pathPattern.'}', $route->getPath())) {
            return true;
        }

        return false;
    }

    private function matchAreas(Route $route): bool
    {
        $areas = $this->getAreas($route);
        if (null === $areas) {
            return false;
        }

        if (in_array('authenticationData', $areas)) {
            return true;
        }

        switch ($this->area) {
            case 'admin':
                return $this->mathAreasAdmin($areas);
            case 'smartems':
                return $this->mathAreasSmartems($areas);
            case 'vpnsecuritysuite':
                return $this->mathAreasVpnSecuritySuite($areas);
            case 'smartemsvpnsecuritysuite':
                return $this->mathAreasSmartems($areas) || $this->mathAreasVpnSecuritySuite($areas);
        }

        return false;
    }

    private function mathAreasAdmin(array $areas): bool
    {
        if (in_array('admin:scep', $areas) && $this->featureManager->isScepAvailable()) {
            return true;
        }

        if (in_array('admin:vpnsecuritysuite', $areas) && $this->featureManager->isVpnAvailable()) {
            return true;
        }

        return in_array('admin', $areas);
    }

    private function mathAreasSmartems(array $areas): bool
    {
        return in_array('smartems', $areas);
    }

    private function mathAreasVpnSecuritySuite(array $areas): bool
    {
        return in_array('vpnsecuritysuite', $areas) && $this->featureManager->isVpnAvailable();
    }

    private function getAreas(Route $route): ?array
    {
        $reflectionMethod = $this->controllerReflector->getReflectionMethod($route->getDefault('_controller'));
        if (null === $reflectionMethod) {
            return null;
        }

        $attribute = $this->getAttributesAsAnnotation($reflectionMethod, Areas::class)[0] ?? null;
        if (null !== $attribute) {
            return $attribute->getAreas();
        }

        $attribute = $this->getAttributesAsAnnotation($reflectionMethod->getDeclaringClass(), Areas::class)[0] ?? null;
        if (null !== $attribute) {
            return $attribute->getAreas();
        }

        $attribute = $this->annotationReader->getMethodAnnotation($reflectionMethod, Areas::class);
        if (null !== $attribute) {
            return $attribute->getAreas();
        }

        return null;
    }

    private function getAttributesAsAnnotation($reflection, string $className): array
    {
        $annotations = [];
        if (\PHP_VERSION_ID < 80100) {
            return $annotations;
        }

        foreach ($reflection->getAttributes($className, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $annotations[] = $attribute->newInstance();
        }

        return $annotations;
    }
}
