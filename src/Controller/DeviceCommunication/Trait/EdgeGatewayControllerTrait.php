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

namespace App\Controller\DeviceCommunication\Trait;

use App\DeviceCommunication\Trait\EdgeGatewayCommunicationInterface;
use App\Entity\DeviceType;
use App\Form\DeviceCommunication\EdgeGatewayConfigurationType;
use App\Model\EdgeGatewayResponseModel;
use App\Model\ResponseModel;
use App\Service\ConfigurationManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait EdgeGatewayControllerTrait
{
    // abstract method forces base class to provide valid device communication class
    abstract protected function getDeviceCommunication(): EdgeGatewayCommunicationInterface;

    abstract protected function getDeviceType(): ?DeviceType;

    abstract protected function getConfigurationManager(): ConfigurationManager;

    abstract protected function preAction(Request $request, null|callable $customApplyDenyAccess = null): Response|ResponseModel|null;

    abstract protected function postAction(Request $request, Response|ResponseModel $response): ?Response;

    abstract protected function createForm(string $type, $data = null, array $options = []): FormInterface;

    // using custom method due to possible conflict with other traits (VpnContainerClientTrait)
    public function applyDenyAccessEdgeGateway(Request $request): Response|ResponseModel|null
    {
        if ($this->getConfigurationManager()->isMaintenanceModeEnabled()) {
            $response = new EdgeGatewayResponseModel();
            $response->setError('Under maintenance');

            return $response;
        }

        return null;
    }

    #[Rest\View(serializerGroups: ['edgeGateway:register', 'edgeGateway:configuration'])]
    public function edgeGatewayConfigurationAction(Request $request): Response|ResponseModel
    {
        $response = $this->preAction($request, function ($request) {return $this->applyDenyAccessEdgeGateway($request); });
        if ($response) {
            return $this->postAction($request, $response);
        }

        $validationGroups = \array_merge(
            [
                'Default',
                'edgeGatewayConfiguration',
            ],
            $this->getDeviceCommunication()->getDeviceTypeValidationGroups($this->getDeviceType())
        );

        $form = $this->createForm(EdgeGatewayConfigurationType::class, null, ['allow_extra_fields' => true, 'validation_groups' => $validationGroups]);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->getDeviceCommunication()->processEdgeGatewayRequest($this->getDeviceType(), $request, $form->getData());
        } else {
            $response = $this->getDeviceCommunication()->prepareErrorResponse($this->getDeviceType(), $request, $form);
        }

        return $this->postAction($request, $response);
    }
}
