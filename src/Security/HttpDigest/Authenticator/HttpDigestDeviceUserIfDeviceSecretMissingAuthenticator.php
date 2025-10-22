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

namespace App\Security\HttpDigest\Authenticator;

use App\Security\HttpDigest\DigestData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class HttpDigestDeviceUserIfDeviceSecretMissingAuthenticator extends AbstractHttpDigestAuthenticator
{
    public function createPassport(Request $request, DigestData $digestData): Passport
    {
        $deviceSecret = $this->getCredentialsDeviceSecret($request);
        if (!$deviceSecret) {
            // No deviceSecret try Device User
            return $this->createDeviceUserPassport($request, $digestData);
        }

        // DeviceSecret exists authenticate using DeviceSecret
        $deviceSecretPassport = $this->createDeviceSecretPassport($request, $digestData, $deviceSecret);
        // Authentication failed
        if (!$deviceSecretPassport) {
            throw new CustomUserMessageAuthenticationException('Invalid credentials');
        }

        return $deviceSecretPassport;
    }
}
