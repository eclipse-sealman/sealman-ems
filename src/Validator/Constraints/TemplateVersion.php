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
class TemplateVersion extends Constraint
{
    public $messageVpnDisabled = 'validation.templateVersion.vpnDisabled';
    public $messageVariablesDisabled = 'validation.templateVersion.variablesDisabled';
    public $messageMasqueradesDisabled = 'validation.templateVersion.masqueradesDisabled';
    public $messageMasqueradeTypeDisabledMasquaradesMustBeEmpty = 'validation.templateVersion.masqueradeTypeDisabledMasquaradesMustBeEmpty';
    public $messageMasqueradeTypeDefaultMasquaradesMustBeEmpty = 'validation.templateVersion.masqueradeTypeDefaultMasquaradesMustBeEmpty';
    public $messageMasqueradeTypeAdvancedMasquaradesAtLeastOne = 'validation.templateVersion.masqueradeTypeAdvancedMasquaradesAtLeastOne';
    public $messageRequired = 'validation.required';
    public $messageConfig1Disabled = 'validation.templateVersion.config1Disabled';
    public $messageConfig2Disabled = 'validation.templateVersion.config2Disabled';
    public $messageConfig3Disabled = 'validation.templateVersion.config3Disabled';
    public $messageConfigInvalid = 'validation.templateVersion.configInvalid';
    public $messageConfigInvalidFeature = 'validation.templateVersion.configInvalidFeature';
    public $messageConfigInvalidDeviceType = 'validation.templateVersion.configInvalidDeviceType';
    public $messageFirmware1Disabled = 'validation.templateVersion.firmware1Disabled';
    public $messageFirmware2Disabled = 'validation.templateVersion.firmware2Disabled';
    public $messageFirmware3Disabled = 'validation.templateVersion.firmware3Disabled';
    public $messageFirmwareInvalid = 'validation.templateVersion.firmwareInvalid';
    public $messageFirmwareInvalidFeature = 'validation.templateVersion.firmwareInvalidFeature';
    public $messageFirmwareInvalidDeviceType = 'validation.templateVersion.firmwareInvalidDeviceType';
    public $messageEndpointDevicesDisabled = 'validation.templateVersion.endpointDevicesDisabled';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
