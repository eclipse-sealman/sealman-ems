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

use App\Entity\Certificate;
use App\Entity\DeviceSecret;
use App\Entity\DeviceTypeSecret;
use Symfony\Component\HttpFoundation\Request;
use App\Service\Helper\CertificateManagerTrait;
use App\Service\Helper\DeviceSecretManagerTrait;
use Carve\ApiBundle\Service\Helper\EntityManagerTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait DeviceAuthenticatorHelperTrait
{
    use CertificateManagerTrait;
    use DeviceCommunicationFactoryTrait;
    use DeviceSecretManagerTrait;
    use EntityManagerTrait;

    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $token = new UsernamePasswordToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());

        return $token;
    }

    protected function getCredentialsDeviceTypeSecret(Request $request): ?DeviceTypeSecret
    {
        $deviceType = $this->deviceCommunicationFactory->getRequestedDeviceType($request);
        if (!$deviceType) {
            return null;
        }

        return $deviceType->getDeviceTypeSecretCredential();
    }

    protected function getCredentialsDeviceSecret(Request $request): ?DeviceSecret
    {
        $deviceTypeSecret = $this->getCredentialsDeviceTypeSecret($request);

        if (!$deviceTypeSecret) {
            return null;
        }

        $device = $this->deviceCommunicationFactory->getRequestedDevice($request);
        if (!$device) {
            return null;
        }

        return $this->getRepository(DeviceSecret::class)->findOneBy([
            'deviceTypeSecret' => $deviceTypeSecret,
            'device' => $device,
        ]);
    }

    // Wrapper method is designed to add SecretLog if needed
    protected function getCredentialsDeviceSecretValue(DeviceSecret $deviceSecret): ?string
    {
        return $this->deviceSecretManager->getDecryptedSecretValue($deviceSecret);
    }

    protected function getCredentialsDeviceCertificate(Request $request): ?Certificate
    {
        $deviceType = $this->deviceCommunicationFactory->getRequestedDeviceType($request);
        if (!$deviceType) {
            return null;
        }

        $deviceTypeCertificateType = $deviceType->getDeviceTypeCertificateTypeCredential();
        if (!$deviceTypeCertificateType) {
            return null;
        }

        $device = $this->deviceCommunicationFactory->getRequestedDevice($request);
        if (!$device) {
            return null;
        }

        return $this->getRepository(Certificate::class)->findOneBy([
            'certificateType' => $deviceTypeCertificateType,
            'device' => $device,
        ]);
    }

    protected function getCredentialsDeviceCertificateContent(Request $request): ?string
    {
        $deviceCertificate = $this->getCredentialsDeviceCertificate($request);
        if (!$deviceCertificate) {
            return null;
        }

        return $this->certificateManager->getCertificate($deviceCertificate);
    }
}
