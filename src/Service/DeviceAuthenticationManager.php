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

namespace App\Service;

use App\Entity\DeviceFailedLoginAttempt;
use App\Entity\DeviceType;
use App\Enum\AuthenticationMethod;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class DeviceAuthenticationManager
{
    use EntityManagerTrait;
    use DeviceCommunicationFactoryTrait;

    /**
     * @var DeviceFailedLoginAttempt
     */
    protected $deviceFailedLoginAttempt;

    /**
     * @var ?Request
     */
    protected $request;

    /**
     * @var ?DeviceType
     */
    protected $deviceType;

    /**
     * @var bool
     */
    protected $deviceFailedLoginAttemptSaved = false;

    /**
     * @var bool
     */
    protected $startUnauthorizedResponse = false;

    /**
     * @var ?string
     */
    protected $userIdentifier = null;

    public function getDeviceFailedLoginAttempt(): DeviceFailedLoginAttempt
    {
        return $this->deviceFailedLoginAttempt;
    }

    public function setDeviceFailedLoginAttempt(DeviceFailedLoginAttempt $deviceFailedLoginAttempt)
    {
        $this->deviceFailedLoginAttempt = $deviceFailedLoginAttempt;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request)
    {
        $this->request = $request;
    }

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    public function getDeviceFailedLoginAttemptSaved(): bool
    {
        return $this->deviceFailedLoginAttemptSaved;
    }

    public function setDeviceFailedLoginAttemptSaved(bool $deviceFailedLoginAttemptSaved)
    {
        $this->deviceFailedLoginAttemptSaved = $deviceFailedLoginAttemptSaved;
    }

    public function getStartUnauthorizedResponse(): bool
    {
        return $this->startUnauthorizedResponse;
    }

    public function setStartUnauthorizedResponse(bool $startUnauthorizedResponse)
    {
        $this->startUnauthorizedResponse = $startUnauthorizedResponse;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier)
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function processUnauthorizedTermination()
    {
        // $this->getDeviceFailedLoginAttemptSaved() will be set if DeviceFailedLoginAttempt already saved using LoginFailureEvent
        // $this->getStartUnauthorizedResponse() will be set if authenticator unauthorized response is part of standard process e.g. HTTP DIGEST - user didn't login yet,
        // $this->getRequest() will be set if one of AuthenticationRequestMatchers matched this request,
        // if $this->getRequest() not set, unauthorized response is not related to device communication request
        if (!$this->getDeviceFailedLoginAttemptSaved() && !$this->getStartUnauthorizedResponse() && $this->getRequest() && $this->getDeviceType()) {
            $deviceFailedLoginAttempt = new DeviceFailedLoginAttempt();
            $deviceFailedLoginAttempt->setDeviceType($this->getDeviceType());
            $deviceFailedLoginAttempt->setUserIdentifier($this->getUserIdentifier());
            $deviceFailedLoginAttempt->setAuthenticationMethod($this->getDeviceType()->getAuthenticationMethod());
            $deviceFailedLoginAttempt->setUrl($this->getRequest()->getUri());
            $deviceFailedLoginAttempt->setRemoteHost($this->getRequest()->getClientIp());

            $this->entityManager->persist($deviceFailedLoginAttempt);
            $this->entityManager->flush();

            // this should not happen, but just in case, it will prevent DeviceFailedLoginAttempt record duplication
            $this->setDeviceFailedLoginAttemptSaved(true);
        }
    }

    public function processLoginFailureEvent(LoginFailureEvent $event)
    {
        $request = $event->getRequest();
        $firewallName = $event->getFirewallName();
        $deviceType = $this->deviceCommunicationFactory->getRequestedDeviceType($request);

        // Communication firewall suffix has to match App\Enum\AuthenticationMethod enum to register device failed login attempts
        $authenticationMethod = null;
        foreach (AuthenticationMethod::cases() as $authenticationMethodCase) {
            if (str_starts_with($firewallName, 'communication_'.$authenticationMethodCase->value)) {
                $authenticationMethod = $authenticationMethodCase;
                break;
            }
        }

        // This means that user failed to login using different firewall than one of communication_* ones
        if (!$authenticationMethod) {
            return;
        }

        // Use fallback userIdentifier if one not provided by Badge
        $userIdentifier = $this->getUserIdentifier();
        if ($event->getPassport() && $event->getPassport()->hasBadge(UserBadge::class)) {
            $userIdentifier = $event->getPassport()->getBadge(UserBadge::class)->getUserIdentifier();
        }

        if (!$this->getStartUnauthorizedResponse()) {
            $deviceFailedLoginAttempt = new DeviceFailedLoginAttempt();
            $deviceFailedLoginAttempt->setDeviceType($deviceType);
            $deviceFailedLoginAttempt->setUserIdentifier($userIdentifier);
            $deviceFailedLoginAttempt->setAuthenticationMethod($authenticationMethod);
            $deviceFailedLoginAttempt->setUrl($request->getUri());
            $deviceFailedLoginAttempt->setRemoteHost($request->getClientIp());

            $this->entityManager->persist($deviceFailedLoginAttempt);
        }

        $this->entityManager->flush();

        $this->setDeviceFailedLoginAttemptSaved(true);
    }
}
