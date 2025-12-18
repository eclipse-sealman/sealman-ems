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

namespace Tests\Utilities\Utility;

class Accessor
{
    public static function invoke(object|string $object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass(static::getClass($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }

    public static function getProperty(object|string $object, string $propertyName)
    {
        $reflection = new \ReflectionClass(static::getClass($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public static function getClass(object|string $object): string
    {
        if (is_string($object)) {
            return $object;
        }

        return get_class($object);
    }
}
