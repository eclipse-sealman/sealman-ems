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

namespace App;

use App\DependencyInjection\DoctrineSslExtension;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->registerExtension(new DoctrineSslExtension());
    }

    public function process(ContainerBuilder $container): void
    {
        // This is thightly coupled with "security.firewalls.keep_ttl_refresh" definition in config/packages/security.yaml
        // It changes ttl_update configuration to false for this authenticator
        $refreshTokenAuthenticator = $container->getDefinition('security.authenticator.refresh_jwt.keep_ttl_refresh');

        $options = $refreshTokenAuthenticator->getArgument(6);
        $options['ttl_update'] = false;
        $refreshTokenAuthenticator->replaceArgument(6, $options);
    }
}
