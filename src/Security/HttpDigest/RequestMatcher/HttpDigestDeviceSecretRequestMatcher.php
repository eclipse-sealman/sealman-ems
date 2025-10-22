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

namespace App\Security\HttpDigest\RequestMatcher;

use App\Entity\DeviceType;
use App\Enum\AuthenticationMethod;
use App\Enum\CredentialsSource;
use App\Security\AbstractAuthenticationRequestMatcher;

class HttpDigestDeviceSecretRequestMatcher extends AbstractAuthenticationRequestMatcher
{
    public function isAuthenticationMethodValid(DeviceType $deviceType, ?bool $isFirmwareSecured = null): bool
    {
        // Not handling unsecured firmware, doesn't matter deviceType settings
        if (false === $isFirmwareSecured) {
            return false;
        }

        if (AuthenticationMethod::DIGEST == $deviceType->getAuthenticationMethod()) {
            if (CredentialsSource::SECRET == $deviceType->getCredentialsSource()) {
                return true;
            }
        }

        return false;
    }
}
