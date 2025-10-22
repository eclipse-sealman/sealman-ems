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

use App\Exception\MissingGroupException;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\RadiusManagerTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

class RadiusCheckCredentialsSubscriber implements EventSubscriberInterface
{
    use RadiusManagerTrait;
    use EntityManagerTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            CheckPassportEvent::class => ['onCheckPassport', 200],
        ];
    }

    public function onCheckPassport(CheckPassportEvent $event)
    {
        if (!$event->getPassport() || !$event->getPassport()->hasBadge(UserBadge::class)) {
            return;
        }

        $user = $event->getPassport()->getBadge(UserBadge::class)->getUser();
        if (!$user->getRadiusUser()) {
            return;
        }

        if (!$event->getPassport()->hasBadge(PasswordCredentials::class)) {
            return;
        }

        $passwordCredentials = $event->getPassport()->getBadge(PasswordCredentials::class);

        if ($passwordCredentials->isResolved()) {
            return;
        }

        if ($this->radiusManager->checkCredentials($user->getUsername(), $passwordCredentials->getPassword())) {
            $valid = $this->radiusManager->applyMapping($user);

            if (!$valid) {
                throw new MissingGroupException();
            }

            $passwordCredentials->markResolved();
        }
    }
}
