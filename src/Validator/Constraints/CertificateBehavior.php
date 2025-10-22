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
class CertificateBehavior extends Constraint
{
    public $messageGenerateCertificateRoleSmartemsNotSupported = 'validation.certificateBehavior.generateCertificateRoleSmartemsNotSupported';
    public $messageRevokeCertificateRoleSmartemsNotSupported = 'validation.certificateBehavior.revokeCertificateRoleSmartemsNotSupported';
    public $messageGenerateCertificateEnabledRequired = 'validation.certificateBehavior.generateCertificateEnabledRequired';
    public $messageRevokeCertificateDisabledRequired = 'validation.certificateBehavior.revokeCertificateDisabledRequired';
    public $messageNotSupportedByCertificateBehavior = 'validation.certificateBehavior.notSupportedByCertificateBehavior';
    public $messagePkiNotAvailable = 'validation.certificateBehavior.pkiNotAvailable';
    public $messageVpnNotAvailable = 'validation.certificateBehavior.vpnNotAvailable';
    public $messageCertificateNotGenerated = 'validation.certificateBehavior.certificateNotGenerated';
    public $messageInvalid = 'validation.certificateBehavior.certificateTypeNotAvailable';

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
