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

use App\Exception\LogsException;
use App\Provider\Interface\VpnProviderInterface;
use App\Provider\OpnSenseVpnProvider;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\HttpClientTrait;
use App\Service\Helper\VpnLogManagerTrait;

class VpnProviderFactory
{
    use VpnLogManagerTrait;
    use ConfigurationManagerTrait;
    use HttpClientTrait;

    public function getProvider(): VpnProviderInterface
    {
        $configuration = $this->getConfiguration();
        $url = $configuration->getOpnsenseUrl();

        if (!$configuration->getOpnsenseUrl()) {
            throw new LogsException($this->vpnLogManager->createLogError('log.vpnProviders.noOpnsenseUrl'));
        }

        return new OpnSenseVpnProvider(
            $this->httpClient,
            $url,
            $configuration->getOpnsenseTimeout(),
            $configuration->getVerifyOpnsenseSslCertificate(),
            $configuration->getOpnsenseApiKey(),
            $configuration->getOpnsenseApiSecret(),
        );
    }
}
