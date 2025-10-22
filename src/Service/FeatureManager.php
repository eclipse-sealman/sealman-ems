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

use Symfony\Contracts\Service\Attribute\Required;

class FeatureManager
{
    protected bool $featureScepEnabled;

    protected bool $featureVpnEnabled;

    #[Required]
    public function setFeatureScepEnabled(bool $featureScepEnabled)
    {
        $this->featureScepEnabled = $featureScepEnabled;
    }

    #[Required]
    public function setFeatureVpnEnabled(bool $featureVpnEnabled)
    {
        $this->featureVpnEnabled = $featureVpnEnabled;
    }

    public function isScepAvailable(): bool
    {
        return $this->featureScepEnabled;
    }

    public function isVpnAvailable(): bool
    {
        return $this->isScepAvailable() && $this->featureVpnEnabled;
    }
}
