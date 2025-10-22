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

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Helper\ActorProviderTrait;
use App\Service\Helper\AuthenticationManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\RequestStackTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    use EntityManagerTrait;
    use AuthenticationManagerTrait;
    use RequestStackTrait;
    use ActorProviderTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
            Events::JWT_CREATED => 'onJWTCreated',
            Events::JWT_AUTHENTICATED => 'onJWTAuthenticated',
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
        ];
    }

    /**
     * Do not require use to provide TOTP when JWT is created using following routes:
     * - /web/api/authentication/token/refresh
     * - /web/api/authentication/token/keep-ttl-refresh.
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $supportedRoutes = [
            '/web/api/authentication/token/refresh',
            '/web/api/authentication/token/keep-ttl-refresh',
        ];

        if (!in_array($request->getPathInfo(), $supportedRoutes)) {
            // Only act on refresh token route
            return;
        }

        $user = $event->getUser();
        if (!$user) {
            return;
        }

        if (!$user instanceof User) {
            return;
        }

        $user->setTotpRequired(false);

        $data = $event->getData();
        $data['totpAuthenticated'] = true;

        $event->setData($data);
    }

    public function onJWTAuthenticated(JWTAuthenticatedEvent $event)
    {
        $payload = $event->getPayload();

        // totpAuthenticated is added to JWT payload when TOTP is successfully verified
        $totpAuthenticated = $payload['totpAuthenticated'] ?? false;
        if ($totpAuthenticated) {
            $token = $event->getToken();
            if (!$token) {
                return;
            }

            $user = $token->getUser();
            if (!$user) {
                return;
            }

            $user->setTotpRequired(false);
        }
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $data = $this->authenticationManager->extendAuthenticationData($user, $data);
        $event->setData($data);

        $request = $this->requestStack->getCurrentRequest();
        // Only register login attempts when directly using login form
        if ('/web/api/authentication/login_check' === $request->getPathInfo()) {
            // lastLoginAt will be flushed by registerLoginAttempt()
            $user->setLastLoginAt(new \DateTime());
            // Note: resetLoginAttempts function does not execute entityManager->flush()
            $this->authenticationManager->resetLoginAttempts($user);
            $this->authenticationManager->registerLoginAttempt(true, false, $user->getUsername(), $user);
        }
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $data = json_decode($event->getRequest()->getContent(), true);
        if (!$data) {
            // Do nothing
            return;
        }

        // Use system user to blame failed login attempts
        $this->actorProvider->setSystemActor();

        $username = $data['username'];
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($user) {
            // Note: increaseFailedLoginAttempts function does not execute entityManager->flush()
            $this->authenticationManager->increaseFailedLoginAttempts($user);
        }

        $this->authenticationManager->registerLoginAttempt(false, false, $username, $user);
    }
}
