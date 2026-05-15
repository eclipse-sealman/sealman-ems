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
    public string $messageRequiredField = 'validation.deviceType.requiredField';
    public string $messageNotUsedField = 'validation.deviceType.unusedField';
    public string $messageFirmwareNotUsedCannotEnableMinRsrp = 'validation.deviceType.firmwareNotUsedCannotEnableMinRsrp';
    public string $messageFirmwareNotUsedCannotEnableHardwares = 'validation.deviceType.firmwareNotUsedCannotEnableHasHardwares';
    public string $messageCannotDisableHardwaresFirmwareHardwareExists = 'validation.deviceType.cannotDisableHasHardwaresFirmwareHardwareExists';
    public string $messageConfigNotUsedCannotEnableMinRsrp = 'validation.deviceType.configNotUsedCannotEnableMinRsrp';
    public string $messageAlwaysReinstallConfigNotAvailable = 'validation.deviceType.alwaysReinstallConfigNotAvailable';
    public string $messageHasCertificatesNotAvailable = 'validation.deviceType.hasCertificatesNotAvailable';
    public string $messageEnableCertificatesAutoRenewNotAvailable = 'validation.deviceType.enableCertificatesAutoRenewNotAvailable';
    public string $messageEnableSubjectAltNameNotAvailable = 'validation.deviceType.enableSubjectAltNameNotAvailable';
    public string $messageHasVpnNotAvailable = 'validation.deviceType.hasVpnNotAvailable';
    public string $messageHasEndpointDevicesNotAvailable = 'validation.deviceType.hasEndpointDevicesNotAvailable';
    public string $messageHasMasqueradesNotAvailable = 'validation.deviceType.hasMasqueradesNotAvailable';
    public string $messageRoutePrefixUsed = 'validation.deviceType.routePrefixUsed';
    public string $messageRoutePrefixStart = 'validation.deviceType.routePrefixStart';
    public string $messageRoutePrefixReserved = 'validation.deviceType.routePrefixReserved';
    public string $messagePropertyRequired = 'validation.deviceType.propertyRequired';
    public string $messagePropertyRequiredInCommunication = 'validation.deviceType.propertyRequiredInCommunication';
    public string $messageCertificateCategoryRequired = 'validation.deviceType.certificateCategoryRequired';
    public string $messageCertificateInvalidCertificateEntity = 'validation.deviceType.certificateInvalidCertificateEntity';
    public string $messageCertificateInvalidCertificateCategory = 'validation.deviceType.certificateInvalidCertificateCategory';
    public string $messageCredentialsSourceMissing = 'validation.deviceType.credentialsSourceMissing';
    public string $messageDeviceTypeSecretCredentialMissing = 'validation.deviceType.deviceTypeSecretCredentialMissing';
    public string $messageDeviceTypeSecretCredentialInvalid = 'validation.deviceType.deviceTypeSecretCredentialInvalid';
    public string $messageDeviceTypeCertificateTypeCredentialMissing = 'validation.deviceType.deviceTypeCertificateTypeCredentialMissing';
    public string $messageDeviceTypeCertificateTypeCredentialInvalid = 'validation.deviceType.deviceTypeCertificateTypeCredentialInvalid';
    public string $messageFirmwareSchemaMissing = 'validation.deviceType.firmwareSchemaMissing';
    public string $messageFirmwareSchemaAnyCannotHaveUpdatePath = 'validation.deviceType.firmwareSchemaAnyCannotHaveUpdatePath';
    public string $messageFirmwareVersionNotMatchingSchema = 'validation.deviceType.firmwareVersionNotMatchingSchema';
    public string $messageDeviceTypeCertificateTypeMTlsScepAuthenticationMissing = 'validation.deviceType.deviceTypeCertificateTypeMTlsScepAuthenticationMissing';
    public string $messageDeviceTypeCertificateTypeMTlsScepAuthenticationInvalid = 'validation.deviceType.deviceTypeCertificateTypeMTlsScepAuthenticationInvalid';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
