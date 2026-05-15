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

use App\Entity\DeviceMTlsAuthentication;
use App\Entity\DeviceType;
use App\Enum\CrlType;
use App\Service\Helper\HttpClientTrait;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class HttpMTlsAuthenticator extends AbstractHttpMTlsAuthenticator
{
    use HttpClientTrait;

    /**
     * Method should return username for UserBadge (in SelfValidatingPassport) or throw BadCredentialsException.
     */
    protected function authenticateMTls(string $requestCertificateContent, array $requestCertificateArray, string $username, string $certificateSerial, DeviceType $deviceType): string
    {
        $queryBuilder = $this->getRepository(DeviceMTlsAuthentication::class)->createQueryBuilder('da');
        $queryBuilder->andWhere(':deviceType MEMBER OF da.deviceTypes');
        $queryBuilder->setParameter('deviceType', $deviceType);

        $deviceMTlsAuthentications = $queryBuilder->getQuery()->getResult();

        foreach ($deviceMTlsAuthentications as $deviceMTlsAuthentication) {
            if ($this->verifyDeviceMTlsAuthentication($requestCertificateContent, $deviceMTlsAuthentication, $certificateSerial)) {
                return $username;
            }
        }

        throw new BadCredentialsException('Invalid certificate credentials.');
    }

    protected function verifyDeviceMTlsAuthentication(string $requestCertificateContent, DeviceMTlsAuthentication $deviceMTlsAuthentication, string $certificateSerial): bool
    {
        $caContent = $deviceMTlsAuthentication->getCertificateCa();
        // This should be handled by validators keeping it for code safety
        if (null === $caContent) {
            return false;
        }

        switch ($deviceMTlsAuthentication->getCrlType()) {
            case CrlType::URL:
                $crlUrl = $deviceMTlsAuthentication->getCertificateCrlUrl();
                // This should be handled by validators keeping it for code safety
                if (null === $crlUrl) {
                    return false;
                }

                $crlContent = $this->getCrlContentFromUrl($crlUrl);
                $validateCrl = true;

                break;
            case CrlType::PEM:
                $crlContent = $deviceMTlsAuthentication->getCertificateCrl();
                $validateCrl = true;

                break;
            case CrlType::NONE:
                $crlContent = null;
                $validateCrl = false;

                break;
            default:
               throw new \Exception('Unknown CrlType "'.$deviceMTlsAuthentication->getCrlType().'"');
        }

        return $this->verifyCertificate($requestCertificateContent, $caContent, $certificateSerial, $validateCrl, $crlContent);
    }

    protected function getCrlContentFromUrl(string $crlUrl): null|string
    {
        try {
            $response = $this->httpClient->request('GET', $crlUrl);
            // $response is asynchronous, calling getStatusCode() will force waiting for entire response
            $statusCode = $response->getStatusCode();

            if (200 !== $statusCode) {
                return null;
            }

            return $response->getContent();
        } catch (\Exception $exception) {
            return null;
        }
    }
}
