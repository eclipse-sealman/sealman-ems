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
    public string $messageVpnDisabled = 'validation.templateVersion.vpnDisabled';
    public string $messageVariablesDisabled = 'validation.templateVersion.variablesDisabled';
    public string $messageMasqueradesDisabled = 'validation.templateVersion.masqueradesDisabled';
    public string $messageMasqueradeTypeDisabledMasquaradesMustBeEmpty = 'validation.templateVersion.masqueradeTypeDisabledMasquaradesMustBeEmpty';
    public string $messageMasqueradeTypeDefaultMasquaradesMustBeEmpty = 'validation.templateVersion.masqueradeTypeDefaultMasquaradesMustBeEmpty';
    public string $messageMasqueradeTypeAdvancedMasquaradesAtLeastOne = 'validation.templateVersion.masqueradeTypeAdvancedMasquaradesAtLeastOne';
    public string $messageRequired = 'validation.required';
    public string $messageConfig1Disabled = 'validation.templateVersion.config1Disabled';
    public string $messageConfig2Disabled = 'validation.templateVersion.config2Disabled';
    public string $messageConfig3Disabled = 'validation.templateVersion.config3Disabled';
    public string $messageConfigInvalid = 'validation.templateVersion.configInvalid';
    public string $messageConfigInvalidFeature = 'validation.templateVersion.configInvalidFeature';
    public string $messageConfigInvalidDeviceType = 'validation.templateVersion.configInvalidDeviceType';
    public string $messageFirmware1Disabled = 'validation.templateVersion.firmware1Disabled';
    public string $messageFirmware2Disabled = 'validation.templateVersion.firmware2Disabled';
    public string $messageFirmware3Disabled = 'validation.templateVersion.firmware3Disabled';
    public string $messageFirmwareInvalid = 'validation.templateVersion.firmwareInvalid';
    public string $messageFirmwareInvalidFeature = 'validation.templateVersion.firmwareInvalidFeature';
    public string $messageFirmwareInvalidDeviceType = 'validation.templateVersion.firmwareInvalidDeviceType';
    public string $messageEndpointDevicesDisabled = 'validation.templateVersion.endpointDevicesDisabled';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
