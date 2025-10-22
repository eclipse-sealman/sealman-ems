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

namespace App\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Helper model to use with Batch Disable Enable of Device (to correctly handle useableCertificate mapping).
 */
class UseableCertificateCollection
{
    /**
     * Helper field for certificates deny handling. Using UsableCertificate model.
     * No validation added - so no CertificateBehavior validator will be executed - VpnManager will handle invalid values.
     */
    private Collection $useableCertificates;

    public function __construct()
    {
        $this->useableCertificates = new ArrayCollection();
    }

    public function getUseableCertificates(): Collection
    {
        return $this->useableCertificates;
    }

    public function setUseableCertificates(Collection $useableCertificates)
    {
        $this->useableCertificates = $useableCertificates;
    }
}
