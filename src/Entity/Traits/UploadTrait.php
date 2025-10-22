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

namespace App\Entity\Traits;

use function Symfony\Component\String\u;

trait UploadTrait
{
    public function getUploadDir(string $field): ?string
    {
        $reflectionClass = new \ReflectionClass($this);
        $dirPart = u($reflectionClass->getShortName())->snake();

        return 'uploads/'.$dirPart.'/'.$this->getId().'/';
    }
}
