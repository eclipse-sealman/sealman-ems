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

namespace App\Tool;

use App\Service\Helper\SystemUserTrait;
use Gedmo\Tool\ActorProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provides an actor for the extensions using the token storage. Additionally allows to use system user or provide a custom actor.
 *
 * Excluded from services (should not be used directly). Use service `stof_doctrine_extensions.tool.actor_provider` instead.
 */
#[Exclude]
class ActorProvider implements ActorProviderInterface
{
    use SystemUserTrait;

    private ?TokenStorageInterface $tokenStorage;
    private ?AuthorizationCheckerInterface $authorizationChecker;

    private ?UserInterface $customActor = null;

    public function __construct(?TokenStorageInterface $tokenStorage = null, ?AuthorizationCheckerInterface $authorizationChecker = null)
    {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function setCustomActor(?UserInterface $actor = null): void
    {
        $this->customActor = $actor;
    }

    public function setSystemActor()
    {
        $this->setCustomActor($this->getSystemUser());
    }

    public function getActor(): ?UserInterface
    {
        if (null !== $this->customActor) {
            return $this->customActor;
        }

        if (null === $this->tokenStorage || null === $this->authorizationChecker) {
            return null;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token || !$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return null;
        }

        return $token->getUser();
    }
}
