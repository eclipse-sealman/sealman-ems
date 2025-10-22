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

use App\Entity\DeviceSecret;
use App\Security\DeviceAuthenticatorHelperTrait;
use App\Security\Hasher\AesPasswordHasher;
use App\Security\HttpDigest\DigestData;
use App\Security\HttpDigest\Exception\NonceExpiredCustomUserMessageAccountStatusException;
use App\Security\UserProvider\DeviceSecretUserProvider;
use App\Service\Helper\DeviceAuthenticationManagerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

abstract class AbstractHttpDigestAuthenticator implements AuthenticationEntryPointInterface, AuthenticatorInterface
{
    use DeviceAuthenticatorHelperTrait;
    use DeviceAuthenticationManagerTrait;

    private $httpDigestKey;
    private $httpDigestRealmName;
    private $httpDigestNonceValiditySeconds;

    /**
     * @var AesPasswordHasher
     */
    protected $aesPasswordHasher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DeviceSecretUserProvider
     */
    protected $deviceSecretUserProvider;

    abstract public function createPassport(Request $request, DigestData $digestData): Passport;

    public function __construct(AesPasswordHasher $aesPasswordHasher, DeviceSecretUserProvider $deviceSecretUserProvider, $httpDigestRealmName, $httpDigestKey, $httpDigestNonceValiditySeconds = 300, LoggerInterface $logger = null)
    {
        $this->aesPasswordHasher = $aesPasswordHasher;
        $this->httpDigestRealmName = $httpDigestRealmName;
        $this->httpDigestKey = $httpDigestKey;
        $this->httpDigestNonceValiditySeconds = $httpDigestNonceValiditySeconds;
        $this->logger = $logger;
        $this->deviceSecretUserProvider = $deviceSecretUserProvider;
    }

    public function supports(Request $request): ?bool
    {
        /*
        Have to assume that RequestMatcher will filter out all non digest requests
        Have to support all request to respond with WWW-Authenticate when user has not provieded any credentials yet
        As described in RFC https://datatracker.ietf.org/doc/html/rfc2069#section-2.4
        */
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $header = $request->server->get('PHP_AUTH_DIGEST');

        if (null === $header) {
            // User haven't tried to login yet, disabling creation of  DeviceFailedLoginAttempt
            $this->deviceAuthenticationManager->setStartUnauthorizedResponse(true);

            throw new CustomUserMessageAuthenticationException('Digest credentials missing');
        }

        $digestData = new DigestData($header);

        if (null !== $this->logger) {
            $this->logger->debug(sprintf('Digest Authorization header received from user agent: %s', $header));
        }

        try {
            $digestData->validateAndDecode($this->httpDigestKey, $this->httpDigestRealmName);
        } catch (BadCredentialsException $e) {
            throw new CustomUserMessageAuthenticationException('Digest validation failed');
        }

        // Making sure device failed login attempts has user identifier data if during authentication process exception is thrown (e.g. invalid credentials exception)
        $this->deviceAuthenticationManager->setUserIdentifier($digestData->getUsername());

        return $this->createPassport($request, $digestData);
    }

    /**
     * Method checks digest credentials against deviceSecret, if valid returns Passport
     * If fails to validate returns false
     * If $deviceSecret is not provided tries to get it from $request (parameter needed for userIfSecretMissing CredentialsSource).
     */
    protected function createDeviceSecretPassport(Request $request, DigestData $digestData, ?DeviceSecret $deviceSecret = null): Passport|false
    {
        if (!$deviceSecret) {
            $deviceSecret = $this->getCredentialsDeviceSecret($request);
            if (!$deviceSecret) {
                return false;
            }
        }

        $deviceSecretExpectedPassword = $this->getCredentialsDeviceSecretValue($deviceSecret);
        if (!$deviceSecretExpectedPassword) {
            return false;
        }

        if ($digestData->isNonceExpired()) {
            return false;
        }

        if ($digestData->getUsername() !== $deviceSecret->getSecretName()) {
            return false;
        }

        $serverDigestMd5 = $digestData->calculateServerDigest($deviceSecretExpectedPassword, $request->getMethod());

        if ($serverDigestMd5 !== $digestData->getResponse()) {
            return false;
        }

        // Since user was authenticated using device secret, DeviceSecretUserProvider is forced to be used as user provider.
        // Because DeviceUserProvider is first in providers chain, and username might be the same
        return new SelfValidatingPassport(new UserBadge($digestData->getUsername(), $this->deviceSecretUserProvider->loadUserByIdentifier(...)));
    }

    /**
     * Method created Passport for device user to check digest credentials.
     */
    protected function createDeviceUserPassport(Request $request, DigestData $digestData): Passport
    {
        $customCredentialBadge = new CustomCredentials(
            function ($credentials, UserInterface $user) use ($digestData, $request) {
                if (!$user->getRoleDevice() || $user->getUserIdentifier() !== $digestData->getUsername()) {
                    return false;
                }

                return $this->checkUserCredentialsAgainstDigest($request, $user, $digestData);
            }, '');

        return new Passport(
            new UserBadge($digestData->getUsername()),
            $customCredentialBadge,
        );
    }

    protected function checkUserCredentialsAgainstDigest(Request $request, UserInterface $user, DigestData $digestData): bool
    {
        $password = $this->aesPasswordHasher->unHash($user->getPassword(), $user->getSalt());
        // Plain password needs to be used by Auth Digest
        $serverDigestMd5 = $digestData->calculateServerDigest($password, $request->getMethod());

        if ($serverDigestMd5 !== $digestData->getResponse()) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf("Expected response: '%s' but received: '%s'", $serverDigestMd5, $digestData->getResponse()));
            }

            return false;
        }

        if ($digestData->isNonceExpired()) {
            return false;
        }

        if (null !== $this->logger) {
            $this->logger->info(sprintf('Authentication success for user "%s" with response "%s"', $digestData->getUsername(), $digestData->getResponse()));
        }

        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if (null !== $this->logger) {
            $this->logger->error(sprintf('Authentication exception "%s"', strtr($exception->getMessageKey(), $exception->getMessageData())));
        }

        // When authentication failed try to start again with WWW-Authenticate header
        return $this->start($request, $exception);
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $authenticateHeader = $this->getUnauthorizedHeaderChallenge();

        if ($authException instanceof NonceExpiredCustomUserMessageAccountStatusException) {
            $authenticateHeader = $authenticateHeader.', stale="true"';
        }

        if (null !== $this->logger) {
            $this->logger->debug(sprintf('WWW-Authenticate header sent to user agent: "%s"', $authenticateHeader));
        }

        $response = new Response();
        $response->headers->set('WWW-Authenticate', $authenticateHeader);
        $response->setStatusCode(401);

        return $response;
    }

    protected function getUnauthorizedHeaderChallenge(): string
    {
        $expiryTime = microtime(true) + $this->httpDigestNonceValiditySeconds * 1000;
        $signatureValue = md5($expiryTime.':'.$this->httpDigestKey);
        $nonceValue = $expiryTime.':'.$signatureValue;
        $nonceValueBase64 = base64_encode($nonceValue);

        return sprintf('Digest realm="%s", qop="auth", nonce="%s"', $this->httpDigestRealmName, $nonceValueBase64);
    }
}
