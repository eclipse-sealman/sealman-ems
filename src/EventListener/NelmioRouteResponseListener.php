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

namespace App\EventListener;

use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\FeatureManagerTrait;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NelmioRouteResponseListener
{
    use FeatureManagerTrait;
    use ConfigurationManagerTrait;

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $pathInfo = $event->getRequest()->getPathInfo();
        if (str_starts_with($pathInfo, '/web/doc/default')) {
            throw new NotFoundHttpException();
        }

        if (str_starts_with($pathInfo, '/web/doc/admin')) {
            if ($this->getConfiguration()->getDisableAdminRestApiDocumentation()) {
                throw new NotFoundHttpException();
            }
        }

        if (str_starts_with($pathInfo, '/web/doc/smartems')) {
            if ($this->getConfiguration()->getDisableSmartemsRestApiDocumentation()) {
                throw new NotFoundHttpException();
            }
        }

        if (str_starts_with($pathInfo, '/web/doc/vpnsecuritysuite')) {
            if (!$this->featureManager->isVpnAvailable()) {
                throw new NotFoundHttpException();
            }

            if ($this->getConfiguration()->getDisableVpnSecuritySuiteRestApiDocumentation()) {
                throw new NotFoundHttpException();
            }
        }

        if (str_starts_with($pathInfo, '/web/doc/smartemsvpnsecuritysuite')) {
            if ($this->getConfiguration()->getDisableSmartemsRestApiDocumentation()) {
                throw new NotFoundHttpException();
            }

            if (!$this->featureManager->isVpnAvailable()) {
                throw new NotFoundHttpException();
            }

            if ($this->getConfiguration()->getDisableVpnSecuritySuiteRestApiDocumentation()) {
                throw new NotFoundHttpException();
            }
        }
    }
}
