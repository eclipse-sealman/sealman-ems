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

namespace Tests\Utilities\WebApi\UserAccess;

class UniquenessTestCase
{
    public function __construct(
        public string $deviceName,
        public AccessibleEndpointDevice|NonAccessibleEndpointDevice $element1,
        public AccessibleEndpointDevice|NonAccessibleEndpointDevice $element2,
        public AccessibleEndpointDevice|NonAccessibleEndpointDevice $element3,
        public AccessibleEndpointDevice|NonAccessibleEndpointDevice $element4,
        public AccessibleEndpointDevice|NonAccessibleEndpointDevice $element5,
    ) {
    }

    public function getElements(): array
    {
        return [
            1 => $this->element1,
            2 => $this->element2,
            3 => $this->element3,
            4 => $this->element4,
            5 => $this->element5,
        ];
    }

    public static function getElementOriginalName(int $key): string
    {
        return 'Endpoint device #'.$key;
    }

    public static function getElementOriginalPhysicalIp(int $key): string
    {
        return '1.1.1.'.$key;
    }

    public static function getElementOriginalVirtualIpHostPart(int $key): int
    {
        return $key;
    }

    public static function getElementUpdatedName(int $key): string
    {
        return 'Endpoint device #'.$key.' updated';
    }

    public static function getElementUpdatedPhysicalIp(int $key): string
    {
        return '1.1.'.$key.'.'.$key;
    }

    public static function getElementUpdatedVirtualIpHostPart(int $key): int
    {
        // 5 due to number of elements
        return $key + 5;
    }

    public static function getDataElementName(NonAccessibleEndpointDevice|AccessibleEndpointDevice $element): string
    {
        if ($element instanceof NonAccessibleEndpointDevice) {
            return 'Non-accessible [action = '.$element->action->value.']';
        }

        if ($element instanceof AccessibleEndpointDevice) {
            return 'Accessible [action = '.$element->action->value.']';
        }

        throw new \LogicException('Unsupported element');
    }
}
