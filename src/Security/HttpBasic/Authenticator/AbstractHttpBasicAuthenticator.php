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

namespace App\Security\HttpBasic\Authenticator;

use App\Entity\DeviceSecret;
use App\Security\DeviceAuthenticatorHelperTrait;
use App\Security\UserProvider\DeviceSecretUserProvider;
use App\Service\Helper\DeviceAuthenticationManagerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

abstract class AbstractHttpBasicAuthenticator implements AuthenticationEntryPointInterface, AuthenticatorInterface
{
    use DeviceAuthenticatorHelperTrait;
    use DeviceAuthenticationManagerTrait;

    protected string $httpBasicRealmName;
    protected ?LoggerInterface $logger;

    /**
     * @var DeviceSecretUserProvider
     */
    protected $deviceSecretUserProvider;

    abstract public function createPassport(Request $request, string $username, string $password): Passport;

    public function __construct(string $httpBasicRealmName, DeviceSecretUserProvider $deviceSecretUserProvider, ?LoggerInterface $logger = null)
    {
        $this->httpBasicRealmName = $httpBasicRealmName;
        $this->logger = $logger;
        $this->deviceSecretUserProvider = $deviceSecretUserProvider;
    }

    public function getUnauthorizedHeaderChallenge(): string
    {
        return sprintf('Basic realm="%s"', $this->httpBasicRealmName);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $response = new Response();
        $response->headers->set('WWW-Authenticate', $this->getUnauthorizedHeaderChallenge());
        $response->setStatusCode(401);

        return $response;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('PHP_AUTH_USER');
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->headers->get('PHP_AUTH_USER');
        $password = $request->headers->get('PHP_AUTH_PW', '');

        // Making sure device failed login attempts has user identifier data if during authentication process exception is thrown (e.g. invalid credentials exception)
        $this->deviceAuthenticationManager->setUserIdentifier($username);

        return $this->createPassport($request, $username, $password);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger?->info('Basic authentication failed for user.', ['username' => $request->headers->get('PHP_AUTH_USER'), 'exception' => $exception]);

        return $this->start($request, $exception);
    }

    /**
     * Method checks basic credentials against deviceSecret, if valid returns Passport
     * If fails to validate returns false
     * If $deviceSecret is not provided tries to get it from $request (parameter needed for userIfSecretMissing CredentialsSource).
     */
    protected function createDeviceSecretPassport(Request $request, string $username, string $password, ?DeviceSecret $deviceSecret = null): Passport|false
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

        if ($username !== $deviceSecret->getSecretName()) {
            return false;
        }

        if ($password !== $deviceSecretExpectedPassword) {
            return false;
        }

        // Since user was authenticated using device secret, DeviceSecretUserProvider is forced to be used as user provider.
        // Because DeviceUserProvider is first in providers chain, and username might be the same
        return new SelfValidatingPassport(new UserBadge($username, $this->deviceSecretUserProvider->loadUserByIdentifier(...)));
    }

    /**
     * Method created Passport for device user to check basic credentials.
     */
    protected function createDeviceUserPassport(Request $request, string $username, string $password): Passport
    {
        return new Passport(new UserBadge($username), new PasswordCredentials($password));
    }
}
