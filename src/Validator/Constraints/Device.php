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
    public $messageVpnDisabled = 'validation.device.vpnDisabled';
    public $messageVariablesDisabled = 'validation.device.variablesDisabled';
    public $messageMasqueradesDisabled = 'validation.device.masqueradesDisabled';
    public $messageMasqueradeTypeDisabledMasquaradesMustBeEmpty = 'validation.device.masqueradeTypeDisabledMasquaradesMustBeEmpty';
    public $messageMasqueradeTypeDefaultMasquaradesMustBeEmpty = 'validation.device.masqueradeTypeDefaultMasquaradesMustBeEmpty';
    public $messageMasqueradeTypeAdvancedMasquaradesAtLeastOne = 'validation.device.masqueradeTypeAdvancedMasquaradesAtLeastOne';
    public $messageRequired = 'validation.required';
    public $messageNameNotUnique = 'validation.device.nameNotUnique';
    public $messageFieldNotUnique = 'validation.device.uniqueField';
    public $messageConfig1Disabled = 'validation.device.config1Disabled';
    public $messageConfig2Disabled = 'validation.device.config2Disabled';
    public $messageConfig3Disabled = 'validation.device.config3Disabled';
    public $messageFirmware1Disabled = 'validation.device.firmware1Disabled';
    public $messageFirmware2Disabled = 'validation.device.firmware2Disabled';
    public $messageFirmware3Disabled = 'validation.device.firmware3Disabled';
    public $messageEndpointDevicesDisabled = 'validation.device.endpointDevicesDisabled';
    public $messageRequestDiagnoseDataDisabled = 'validation.device.requestDiagnoseDataDisabled';
    public $messageRequestConfigDataDisabled = 'validation.device.requestConfigDataDisabled';
    public $messageTemplatesDisabled = 'validation.device.templatesDisabled';
    public $messageTemplateDeviceTypeMismatch = 'validation.device.templateDeviceTypeMismatch';
    public $messageGsmDisabled = 'validation.device.gsmDisabled';
    public $messageInvalidChoice = 'validation.choice';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
