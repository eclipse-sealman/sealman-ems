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
use App\DeviceCommunication\RouterDsaCommunication;
use App\Form\DeviceCommunication\RouterDsaType;
use App\Model\ResponseModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouterDsaController extends RouterController
{
    use RouterControllerTrait;

    protected function getDeviceCommunication(): RouterDsaCommunication
    {
        // This should never happen, but testing it to make sure controller doesn't return 500 error
        if (!parent::getDeviceCommunication() instanceof RouterDsaCommunication) {
            throw new NotFoundHttpException();
        }

        return parent::getDeviceCommunication();
    }

    public function applyDenyAccess(Request $request): Response|ResponseModel|null
    {
        if ($this->configurationManager->isMaintenanceModeEnabled()) {
            return new Response('Under maintenance');
        }

        return null;
    }

    public function deviceSupervisorConfigAction(Request $request)
    {
        if ($response = $this->preAction($request)) {
            return $response;
        }

        $validationGroups = \array_merge(
            [
                'Default',
                'deviceSupervisorAgent',
            ],
            $this->getDeviceCommunication()->getDeviceTypeValidationGroups($this->deviceType)
        );

        $form = $this->createForm(RouterDsaType::class, null, ['validation_groups' => $validationGroups]);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->getDeviceCommunication()->processDeviceSupervisorAgent($this->deviceType, $request, $form->getData()); // params order chanmged
        } else {
            $response = $this->getDeviceCommunication()->prepareErrorResponse($this->deviceType, $request, $form);
        }

        return $this->postAction($request, $response);
    }
}
