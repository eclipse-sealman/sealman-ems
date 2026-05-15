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
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class HttpMTlsScepAuthenticator extends AbstractHttpMTlsAuthenticator
{
    use CertificateManagerTrait;

    /**
     * Method should return username for UserBadge (in SelfValidatingPassport) or throw BadCredentialsException.
     */
    protected function authenticateMTls(string $requestCertificateContent, array $requestCertificateArray, string $username, string $certificateSerial, DeviceType $deviceType): string
    {
        $certificateType = $deviceType->getDeviceTypeCertificateTypeMTlsScepAuthentication();
        if (!$certificateType) {
            throw new BadCredentialsException('Invalid device type certificate configuration.');
        }

        $caContent = $this->certificateManager->getCaCertificateForCertificateType($certificateType);
        if (null === $caContent) {
            throw new BadCredentialsException('Invalid certificate authority configuration.');
        }

        try {
            $crlContent = $this->certificateManager->getCrlContentForCertificateType($certificateType);
        } catch (\Exception $e) {
            throw new BadCredentialsException('Invalid certificate revocation list configuration.');
        }

        if (!$this->verifyCertificate($requestCertificateContent, $caContent, $certificateSerial, true, $crlContent)) {
            throw new BadCredentialsException('Invalid certificate credentials.');
        }

        return $username;
    }
}
