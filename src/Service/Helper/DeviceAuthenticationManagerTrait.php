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

use App\Service\DeviceAuthenticationManager;
use Symfony\Contracts\Service\Attribute\Required;

trait DeviceAuthenticationManagerTrait
{
    /**
     * @var DeviceAuthenticationManager
     */
    protected $deviceAuthenticationManager;

    #[Required]
    public function setDeviceAuthenticationManager(DeviceAuthenticationManager $deviceAuthenticationManager)
    {
        $this->deviceAuthenticationManager = $deviceAuthenticationManager;
    }
}
