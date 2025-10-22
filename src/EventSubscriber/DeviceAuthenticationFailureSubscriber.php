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

use App\Service\Helper\DeviceAuthenticationManagerTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

class DeviceAuthenticationFailureSubscriber implements EventSubscriberInterface
{
    use DeviceAuthenticationManagerTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onAuthenticationFailure',
            TerminateEvent::class => 'onTerminateEvent',
        ];
    }

    // This event handler handles authentication failure if credentials are not present
    public function onTerminateEvent(TerminateEvent $event)
    {
        if (Response::HTTP_UNAUTHORIZED == $event->getResponse()->getStatusCode()) {
            $this->deviceAuthenticationManager->processUnauthorizedTermination();
        }
    }

    // This event handler handles authentication failure if credentials are present
    public function onAuthenticationFailure(LoginFailureEvent $event)
    {
        $this->deviceAuthenticationManager->processLoginFailureEvent($event);
    }
}
