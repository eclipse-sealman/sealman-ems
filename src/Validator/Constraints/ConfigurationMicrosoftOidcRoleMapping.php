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
class ConfigurationMicrosoftOidcRoleMapping extends Constraint
{
    public $messageAccessTagsNotSupported = 'validation.configurationMicrosoftOidcRoleMapping.accessTagsNotSupported';
    public $messageOneAccessTagRequired = 'validation.configurationMicrosoftOidcRoleMapping.oneAccessTagRequired';
    public $messageRoleVpnEndpointDevicesNotSupported = 'validation.configurationMicrosoftOidcRoleMapping.roleVpnEndpointDevicesNotSupported';
    public $messageRoleNameNotUnique = 'validation.configurationMicrosoftOidcRoleMapping.roleNameNotUnique';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
