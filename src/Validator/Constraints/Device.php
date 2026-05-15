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
class Device extends Constraint
{
    public string $messageVpnDisabled = 'validation.device.vpnDisabled';
    public string $messageVariablesDisabled = 'validation.device.variablesDisabled';
    public string $messageMasqueradesDisabled = 'validation.device.masqueradesDisabled';
    public string $messageMasqueradeTypeDisabledMasquaradesMustBeEmpty = 'validation.device.masqueradeTypeDisabledMasquaradesMustBeEmpty';
    public string $messageMasqueradeTypeDefaultMasquaradesMustBeEmpty = 'validation.device.masqueradeTypeDefaultMasquaradesMustBeEmpty';
    public string $messageMasqueradeTypeAdvancedMasquaradesAtLeastOne = 'validation.device.masqueradeTypeAdvancedMasquaradesAtLeastOne';
    public string $messageRequired = 'validation.required';
    public string $messageNameNotUnique = 'validation.device.nameNotUnique';
    public string $messageFieldNotUnique = 'validation.device.uniqueField';
    public string $messageConfig1Disabled = 'validation.device.config1Disabled';
    public string $messageConfig2Disabled = 'validation.device.config2Disabled';
    public string $messageConfig3Disabled = 'validation.device.config3Disabled';
    public string $messageFirmware1Disabled = 'validation.device.firmware1Disabled';
    public string $messageFirmware2Disabled = 'validation.device.firmware2Disabled';
    public string $messageFirmware3Disabled = 'validation.device.firmware3Disabled';
    public string $messageEndpointDevicesDisabled = 'validation.device.endpointDevicesDisabled';
    public string $messageRequestDiagnoseDataDisabled = 'validation.device.requestDiagnoseDataDisabled';
    public string $messageRequestConfigDataDisabled = 'validation.device.requestConfigDataDisabled';
    public string $messageTemplatesDisabled = 'validation.device.templatesDisabled';
    public string $messageTemplateDeviceTypeMismatch = 'validation.device.templateDeviceTypeMismatch';
    public string $messageGsmDisabled = 'validation.device.gsmDisabled';
    public string $messageInvalidChoice = 'validation.choice';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
