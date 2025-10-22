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

use App\DeviceCommunication\SgGatewayCommunication;
use App\Form\DeviceCommunication\SgGatewayType;
use App\Model\ResponseModel;
use App\Model\SgGatewayResponseModel;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\View(serializerGroups: ['sgGateway:register', 'sgGateway:configuration'])]
class SgGatewayController extends AbstractDeviceController
{
    protected function getDeviceCommunication(): SgGatewayCommunication
    {
        // This should never happen, but testing it to make sure controller doesn't return 500 error
        if (!parent::getDeviceCommunication() instanceof SgGatewayCommunication) {
            throw new NotFoundHttpException();
        }

        return parent::getDeviceCommunication();
    }

    public function applyDenyAccess(Request $request): Response|ResponseModel|null
    {
        if ($this->configurationManager->isMaintenanceModeEnabled()) {
            $response = new SgGatewayResponseModel();
            $response->setError('Under maintenance');

            return $response;
        }

        return null;
    }

    public function configurationAction(Request $request): Response|ResponseModel
    {
        $response = $this->preAction($request);
        if ($response) {
            return $this->postAction($request, $response);
        }

        // When sending request with Content-Type = dls/diagnosedata $_POST variable is empty (passed data is not interpreted by default)
        // This results in empty data in request
        // This also applies to any Content-Type that is not considered POST Content-Type
        if (0 == $request->request->count()) {
            $parameters = [];
            mb_parse_str($request->getContent(), $parameters);
            $request->request->replace($parameters);
        }

        $validationGroups = \array_merge(
            [
                'Default',
                'sgGatewayConfiguration',
            ],
            $this->getDeviceCommunication()->getDeviceTypeValidationGroups($this->deviceType)
        );

        $form = $this->createForm(SgGatewayType::class, null, ['allow_extra_fields' => true, 'validation_groups' => $validationGroups]);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->getDeviceCommunication()->process($this->deviceType, $request, $form->getData());
        } else {
            $response = $this->getDeviceCommunication()->prepareErrorResponse($this->deviceType, $request, $form);
        }

        return $this->postAction($request, $response);
    }
}
