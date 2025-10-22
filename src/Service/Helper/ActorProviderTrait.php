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

namespace App\Service\Helper;

use App\Tool\ActorProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\Attribute\Required;

trait ActorProviderTrait
{
    /**
     * @var ActorProvider
     */
    protected $actorProvider;

    #[Required]
    public function setActorProvider(
        #[Autowire(service: 'stof_doctrine_extensions.tool.actor_provider')]
        ActorProvider $actorProvider
    ) {
        $this->actorProvider = $actorProvider;
    }
}
