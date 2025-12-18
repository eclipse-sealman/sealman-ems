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

namespace Tests\Utilities\Abstract\Trait;

use App\Service\FeatureManager;

/**
 * Code separated for readability.
 * To be used only with AbstractTestCase class.
 *
 * By default SCEP and VPN features are enabled.
 *
 * Usage:
 *
 * public function mock(): void
 * {
 *      $this->disableFeatureScep();
 *      $this->disableFeatureVpn();
 * }
 */
trait MockFeatureTrait
{
    public function disableFeatureScep(): void
    {
        $this->getService(FeatureManager::class)->setFeatureScepEnabled(false);
    }

    public function disableFeatureVpn(): void
    {
        $this->getService(FeatureManager::class)->setFeatureVpnEnabled(false);
    }

    public function enableFeatureScep(): void
    {
        $this->getService(FeatureManager::class)->setFeatureScepEnabled(true);
    }

    public function enableFeatureVpn(): void
    {
        $this->getService(FeatureManager::class)->setFeatureVpnEnabled(true);
    }
}
