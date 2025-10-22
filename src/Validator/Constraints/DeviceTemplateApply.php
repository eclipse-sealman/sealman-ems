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
class DeviceTemplateApply extends Constraint
{
    public $messageTemplateMissingApplyInvalid = 'validation.deviceTemplateApply.templateMissingApplyInvalid';
    public $messageVariablesDisabled = 'validation.deviceTemplateApply.variablesDisabled';
    public $messageMasqueradeDisabled = 'validation.deviceTemplateApply.masqueradeDisabled';
    public $messageEndpointDevicesDisabled = 'validation.deviceTemplateApply.endpointDevicesDisabled';
    public $messageConfig1Disabled = 'validation.deviceTemplateApply.config1Disabled';
    public $messageConfig2Disabled = 'validation.deviceTemplateApply.config2Disabled';
    public $messageConfig3Disabled = 'validation.deviceTemplateApply.config3Disabled';
    public $messageFirmware1Disabled = 'validation.deviceTemplateApply.firmware1Disabled';
    public $messageFirmware2Disabled = 'validation.deviceTemplateApply.firmware2Disabled';
    public $messageFirmware3Disabled = 'validation.deviceTemplateApply.firmware3Disabled';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
