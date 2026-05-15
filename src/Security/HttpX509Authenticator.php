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

namespace App\Security;

use Carve\ApiBundle\Helper\Arr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class HttpX509Authenticator extends AbstractHttpX509Authenticator
{
    use DeviceAuthenticatorHelperTrait;

    /**
     * Method should return username for UserBadge (in SelfValidatingPassport) or throw BadCredentialsException.
     */
    protected function authenticateX509(Request $request, string $requestCertificateContent, array $requestCertificateArray): string
    {
        $deviceCertificateContent = $this->getCredentialsDeviceCertificateContent($request);

        if (!$deviceCertificateContent) {
            throw new BadCredentialsException('Certificate missing.');
        }

        // Credentials check
        if (!$username = $this->getAuthenticatedUsernameFromCredentialCertificate($deviceCertificateContent, $requestCertificateContent)) {
            throw new BadCredentialsException('Invalid certificate.');
        }

        return $username;
    }

    /**
     * Method validates device and request certificates and if valid returns username 'subject.CN' from certificate.
     */
    protected function getAuthenticatedUsernameFromCredentialCertificate(string $deviceCertificateContent, string $requestCertificateContent): string|false
    {
        $requestCertificateArray = openssl_x509_parse($requestCertificateContent);
        if (!$requestCertificateArray) {
            return false;
        }

        $deviceCertificateArray = openssl_x509_parse($deviceCertificateContent);
        if (!$deviceCertificateArray) {
            return false;
        }

        $keysToBeChecked = [
            'serialNumber',
            'validFrom_time_t',
            'validTo_time_t',
            'subject.C',
            'subject.ST',
            'subject.L',
            'subject.O',
            'subject.CN',
            'subject.emailAddress',
            'issuer.C',
            'issuer.ST',
            'issuer.L',
            'issuer.O',
            'issuer.CN',
            'issuer.emailAddress',
            'signatureTypeSN',
            'signatureTypeLN',
        ];

        foreach ($keysToBeChecked as $key) {
            if (Arr::get($requestCertificateArray, $key) !== Arr::get($deviceCertificateArray, $key)) {
                return false;
            }
        }

        $publicCertificateValidTo = new \DateTime();
        $publicCertificateValidTo->setTimestamp(Arr::get($requestCertificateArray, 'validTo_time_t', 0));

        if ($publicCertificateValidTo < (new \DateTime())) {
            return false;
        }

        // Cannot assume timestamp=0 as above because it will validated as valid
        if (!Arr::has($requestCertificateArray, 'validFrom_time_t')) {
            return false;
        }

        $publicCertificateValidFrom = new \DateTime();
        $publicCertificateValidFrom->setTimestamp(Arr::get($requestCertificateArray, 'validFrom_time_t'));

        if ($publicCertificateValidFrom >= (new \DateTime())) {
            return false;
        }

        $requestCertificateFingerprint = openssl_x509_fingerprint($requestCertificateContent, Arr::get($requestCertificateArray, 'signatureTypeSN'));
        if (false === $requestCertificateFingerprint) {
            return false;
        }

        $deviceCertificateFingerprint = openssl_x509_fingerprint($deviceCertificateContent, Arr::get($requestCertificateArray, 'signatureTypeSN'));
        if (false === $deviceCertificateFingerprint) {
            return false;
        }

        if ($requestCertificateFingerprint !== $deviceCertificateFingerprint) {
            return false;
        }

        return Arr::get($requestCertificateArray, 'subject.CN', false);
    }
}
