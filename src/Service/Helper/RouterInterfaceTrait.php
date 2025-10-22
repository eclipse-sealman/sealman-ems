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

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\Attribute\Required;

// TODO Why this trait is named RouterInterfaceTrait? It should be RouterTrait
// Legacy thing - there was RouterTrait meaning tk800 - maybe SymfonyRouterTrait?
trait RouterInterfaceTrait
{
    /**
     * @var RouterInterface
     */
    protected $routerInterface;

    #[Required]
    public function setRouterInterface(RouterInterface $routerInterface)
    {
        $this->routerInterface = $routerInterface;
    }
}
