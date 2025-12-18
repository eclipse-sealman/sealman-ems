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

class ProcessingTestCase
{
    public function __construct(
        public string $deviceName,
        public null|AccessibleEndpointDevice|NonAccessibleEndpointDevice $element1,
        public null|AccessibleEndpointDevice|NonAccessibleEndpointDevice $element2,
        public null|AccessibleEndpointDevice|NonAccessibleEndpointDevice $element3,
        public null|AccessibleEndpointDevice|NonAccessibleEndpointDevice $element4,
        public null|AccessibleEndpointDevice|NonAccessibleEndpointDevice $element5,
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

    public static function getElementUpdatedName(int $key): string
    {
        return 'Endpoint device #'.$key.' updated';
    }

    public static function getDataElementName(null|NonAccessibleEndpointDevice|AccessibleEndpointDevice $element): string
    {
        if (null === $element) {
            return '(empty)';
        }

        if ($element instanceof NonAccessibleEndpointDevice) {
            return 'Non-accessible [action = '.$element->action->value.']';
        }

        if ($element instanceof AccessibleEndpointDevice) {
            return 'Accessible [action = '.$element->action->value.']';
        }

        throw new \LogicException('Unsupported element');
    }
}
