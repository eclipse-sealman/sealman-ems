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

namespace App\Security;

use App\Service\Helper\DeviceAuthenticationManagerTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

abstract class AbstractAuthenticationRequestMatcher implements RequestMatcherInterface, AuthenticationRequestMatcherInterface
{
    use DeviceCommunicationFactoryTrait;
    use DeviceAuthenticationManagerTrait;

    public function matches(Request $request): bool
    {
        $isFirmwareSecured = null;
        // Checking for download url endpoint (in fact it is checkAuth endpoint for nginx)
        if ('/device/check/auth/firmware' === $request->getPathInfo()) {
            $downloadFirmwareUrlModel = $this->deviceCommunicationFactory->parseDownloadFirmwareUri($request);
            if (null === $downloadFirmwareUrlModel) {
                return false;
            }

            $deviceType = $this->deviceCommunicationFactory->getDeviceTypeBySlug($downloadFirmwareUrlModel->getDeviceTypeSlug());
            if (!$deviceType) {
                return false;
            }

            $deviceCommunication = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($deviceType);
            if (!$deviceCommunication) {
                return false;
            }

            $isFirmwareSecured = $deviceCommunication->isFirmwareSecured();
        } else {
            // Checking for regular device communication endpoints
            $deviceType = $this->deviceCommunicationFactory->getRequestedDeviceType($request);
            if (!$deviceType) {
                return false;
            }
        }

        if ($this->isAuthenticationMethodValid($deviceType, $isFirmwareSecured)) {
            $this->deviceAuthenticationManager->setRequest($request);
            $this->deviceAuthenticationManager->setDeviceType($deviceType);

            return true;
        }

        return false;
    }
}
