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

use App\Entity\DeviceType;
use App\Service\Helper\CertificateManagerTrait;
use Carve\ApiBundle\Helper\Arr;
use phpseclib3\File\X509;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

abstract class AbstractHttpMTlsAuthenticator extends AbstractHttpX509Authenticator
{
    use DeviceAuthenticatorHelperTrait;
    use CertificateManagerTrait;

    /**
     * Method should return username for UserBadge (in SelfValidatingPassport) or throw BadCredentialsException.
     */
    abstract protected function authenticateMTls(string $requestCertificateContent, array $requestCertificateArray, string $username, string $certificateSerial, DeviceType $deviceType): string;

    /**
     * Method should return username for UserBadge (in SelfValidatingPassport) or throw BadCredentialsException.
     */
    protected function authenticateX509(Request $request, string $requestCertificateContent, array $requestCertificateArray): string
    {
        $username = Arr::get($requestCertificateArray, 'subject.CN', null);
        if (null === $username) {
            throw new BadCredentialsException('Invalid certificate.');
        }

        $certificateSerial = Arr::get($requestCertificateArray, 'serialNumber', null);
        if (null === $certificateSerial) {
            throw new BadCredentialsException('Invalid certificate.');
        }

        $deviceType = $this->getDeviceTypeFromRequest($request);
        if (!$deviceType) {
            throw new BadCredentialsException('Invalid device type.');
        }

        return $this->authenticateMTls($requestCertificateContent, $requestCertificateArray, $username, $certificateSerial, $deviceType);
    }

    protected function verifyCertificate(string $requestCertificateContent, string $caContent, string $certificateSerial, bool $validateCrl = false, ?string $crlContent = null): bool
    {
        // Validating if CA is not expired
        $x509 = new X509();
        $x509->loadX509($caContent);
        if (true !== $x509->validateDate()) {
            return false;
        }

        $x509 = new X509();
        $x509->loadX509($requestCertificateContent);
        $x509->loadCA($caContent);

        // Validating if certificate is not expired
        if (true !== $x509->validateDate()) {
            return false;
        }

        // Validating if certificate is signed by CA
        if (true !== $x509->validateSignature()) {
            return false;
        }

        // Validating if certificate is not revoked
        if ($validateCrl) {
            if (null === $crlContent) {
                return false;
            }

            $x509Crl = new X509();
            $x509Crl->loadCRL($crlContent);
            $x509Crl->loadCA($caContent);

            // Validating if CRL is signed by CA
            if (true !== $x509Crl->validateSignature()) {
                return false;
            }

            $revokedCertificate = $x509Crl->getRevoked($certificateSerial);
            if (false !== $revokedCertificate) {
                return false;
            }
        }

        return true;
    }
}
