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

abstract class AbstractHttpX509Authenticator implements AuthenticatorInterface, InteractiveAuthenticatorInterface
{
    use DeviceAuthenticationManagerTrait;

    private string $httpX509CertificateParameterName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Method should return username for UserBadge (in SelfValidatingPassport) or throw BadCredentialsException.
     */
    abstract protected function authenticateX509(Request $request, string $requestCertificateContent, array $requestCertificateArray): string;

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
        if (!$requestCertificateArray) {
            throw new BadCredentialsException('Invalid certificate.');
        }

        $certificateSubjectCn = Arr::get($requestCertificateArray, 'subject.CN', null);
        $this->deviceAuthenticationManager->setUserIdentifier($certificateSubjectCn);

        $username = $this->authenticateX509($request, $requestCertificateContent, $requestCertificateArray);

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

        // Case #1 - certificate content is already in PEM format (e.g. nginx with $ssl_client_escaped_cert), if has extra spaces and new lines (e.g. nginx over traefik with $http_x_forwarded_tls_client_cert), they will be trimmed

        $certificatePem = trim($certificateContent);
        if (openssl_x509_parse($certificatePem)) {
            return $certificatePem;
        }

        // Case #2 - certificate content is url encoded, already in PEM format (e.g. nginx with $http_x_client_ssl_cert), but with extra spaces and new lines (e.g. nginx over traefik with $http_x_forwarded_tls_client_cert)
        $certificatePem = trim(\urldecode($certificateContent));
        if (openssl_x509_parse($certificatePem)) {
            return $certificatePem;
        }

        // Case #3 - certificate content contains multiple certificates divided by comma, also certificate content is storred in one line
        // Traefik case
        $certificatePackage = trim($certificateContent);
        $certificatesArray = explode(',', $certificatePackage);

        $certificatePemContent = $certificatesArray[0] ?? null;
        $certificatePemContent = chunk_split(str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', (string) $certificatePemContent), 64, "\n");

        $certificatePem = '-----BEGIN CERTIFICATE-----'."\n".$certificatePemContent.'-----END CERTIFICATE-----'."\n";
        if (openssl_x509_parse($certificatePem)) {
            return $certificatePem;
        }

        /*
         * TEST:
         * - plain nginx $ssl_client_escaped_cert
         * - nginx over container (or some other proxy) - $http_x_client_ssl_cert
         * - nginx over traefik - $http_x_forwarded_tls_client_cert.
         * - should we add custom header name in configuration.
         */

        throw new \Exception('Invalid certificate content. Error string: '.openssl_error_string().' Certificate content: '.print_r($certificateContent, true));
    }
}
