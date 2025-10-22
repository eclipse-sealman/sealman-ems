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

use App\Service\Helper\DeviceAuthenticationManagerTrait;
use Carve\ApiBundle\Helper\Arr;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class HttpX509Authenticator implements AuthenticatorInterface, InteractiveAuthenticatorInterface
{
    use DeviceAuthenticatorHelperTrait;
    use DeviceAuthenticationManagerTrait;

    private string $httpX509CertificateParameterName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(string $httpX509CertificateParameterName, LoggerInterface $logger = null)
    {
        $this->httpX509CertificateParameterName = $httpX509CertificateParameterName;
        $this->logger = $logger;
    }

    public function supports(Request $request): ?bool
    {
        return null !== $this->getCertificateContent($request);
    }

    public function authenticate(Request $request): Passport
    {
        $requestCertificateContent = $this->getCertificateContent($request);
        if (!$requestCertificateContent) {
            throw new BadCredentialsException('Certificate missing.');
        }

        $requestCertificateArray = openssl_x509_parse($requestCertificateContent);
        if ($requestCertificateArray) {
            // Making sure device failed login attempts has user identifier data if during authentication process exception is thrown (e.g. invalid credentials exception)
            $this->deviceAuthenticationManager->setUserIdentifier(Arr::get($requestCertificateArray, 'subject.CN', null));
        }

        $deviceCertificateContent = $this->getCredentialsDeviceCertificateContent($request);

        if (!$deviceCertificateContent) {
            throw new BadCredentialsException('Certificate missing.');
        }

        // Credentials check
        if (!$username = $this->getAuthenticatedUsernameFromCredentialCertificate($deviceCertificateContent, $requestCertificateContent)) {
            throw new BadCredentialsException('Invalid certificate.');
        }

        return new SelfValidatingPassport(new UserBadge($username));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        if (null !== $this->logger) {
            $this->logger->error(sprintf('Authentication exception "%s"', strtr($exception->getMessageKey(), $exception->getMessageData())));
        }

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    // It't interactive login because client have to choose to provide x509 authentication like in login form (unlike basic or digest where credentials are required)
    public function isInteractive(): bool
    {
        return true;
    }

    protected function getCertificateContent(Request $request): ?string
    {
        $certificateContent = $request->server->get($this->httpX509CertificateParameterName);
        if (null === $certificateContent || '' === $certificateContent) {
            return null;
        }

        return trim(\urldecode($certificateContent));
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
