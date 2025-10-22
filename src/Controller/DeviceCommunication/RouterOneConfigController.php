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

namespace App\Controller\DeviceCommunication;

use App\Controller\DeviceCommunication\Trait\RouterControllerTrait;
use App\DeviceCommunication\RouterOneConfigCommunication;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouterOneConfigController extends AbstractDeviceController
{
    use RouterControllerTrait;

    protected function getDeviceCommunication(): RouterOneConfigCommunication
    {
        // This should never happen, but testing it to make sure controller doesn't return 500 error
        if (!parent::getDeviceCommunication() instanceof RouterOneConfigCommunication) {
            throw new NotFoundHttpException();
        }

        return parent::getDeviceCommunication();
    }
}
