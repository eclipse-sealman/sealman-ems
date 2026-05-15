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

#[\Attribute]
class DeviceMTlsAuthentication extends Constraint
{
    public string $messageCrlRequiredForPem = 'validation.deviceMTlsAuthentication.certificateCrl.crlRequiredForPem';
    public string $messageCrlNotMatchingCa = 'validation.deviceMTlsAuthentication.certificateCrl.crlNotMatchingCa';
    public string $messageCrlUrlRequiredForUrl = 'validation.deviceMTlsAuthentication.certificateCrlUrl.crlUrlRequiredForUrl';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
