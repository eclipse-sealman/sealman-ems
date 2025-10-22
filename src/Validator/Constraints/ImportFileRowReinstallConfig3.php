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

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class ImportFileRowReinstallConfig3 extends Constraint
{
    public $messageConfig3Disabled = 'validation.importFileRow.config3Disabled';
    public $messageConfig3Always = 'validation.importFileRow.config3Always';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
