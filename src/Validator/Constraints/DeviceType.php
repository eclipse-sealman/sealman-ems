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
class DeviceType extends Constraint
{
    public $messageRequiredField = 'validation.deviceType.requiredField';
    public $messageNotUsedField = 'validation.deviceType.unusedField';
    public $messageFirmwareNotUsedCannotEnableMinRsrp = 'validation.deviceType.firmwareNotUsedCannotEnableMinRsrp';
    public $messageConfigNotUsedCannotEnableMinRsrp = 'validation.deviceType.configNotUsedCannotEnableMinRsrp';
    public $messageAlwaysReinstallConfigNotAvailable = 'validation.deviceType.alwaysReinstallConfigNotAvailable';
    public $messageHasCertificatesNotAvailable = 'validation.deviceType.hasCertificatesNotAvailable';
    public $messageEnableCertificatesAutoRenewNotAvailable = 'validation.deviceType.enableCertificatesAutoRenewNotAvailable';
    public $messageEnableSubjectAltNameNotAvailable = 'validation.deviceType.enableSubjectAltNameNotAvailable';
    public $messageHasVpnNotAvailable = 'validation.deviceType.hasVpnNotAvailable';
    public $messageHasEndpointDevicesNotAvailable = 'validation.deviceType.hasEndpointDevicesNotAvailable';
    public $messageHasMasqueradesNotAvailable = 'validation.deviceType.hasMasqueradesNotAvailable';
    public $messageRoutePrefixUsed = 'validation.deviceType.routePrefixUsed';
    public $messageRoutePrefixStart = 'validation.deviceType.routePrefixStart';
    public $messageRoutePrefixReserved = 'validation.deviceType.routePrefixReserved';
    public $messagePropertyRequired = 'validation.deviceType.propertyRequired';
    public $messagePropertyRequiredInCommunication = 'validation.deviceType.propertyRequiredInCommunication';
    public $messageCertificateCategoryRequired = 'validation.deviceType.certificateCategoryRequired';
    public $messageCertificateInvalidCertificateEntity = 'validation.deviceType.certificateInvalidCertificateEntity';
    public $messageCertificateInvalidCertificateCategory = 'validation.deviceType.certificateInvalidCertificateCategory';
    public $messageCredentialsSourceMissing = 'validation.deviceType.credentialsSourceMissing';
    public $messageDeviceTypeSecretCredentialMissing = 'validation.deviceType.deviceTypeSecretCredentialMissing';
    public $messageDeviceTypeSecretCredentialInvalid = 'validation.deviceType.deviceTypeSecretCredentialInvalid';
    public $messageDeviceTypeCertificateTypeCredentialMissing = 'validation.deviceType.deviceTypeCertificateTypeCredentialMissing';
    public $messageDeviceTypeCertificateTypeCredentialInvalid = 'validation.deviceType.deviceTypeCertificateTypeCredentialInvalid';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
