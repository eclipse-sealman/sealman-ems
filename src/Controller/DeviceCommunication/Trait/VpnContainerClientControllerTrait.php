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

use App\DeviceCommunication\Trait\VpnContainerClientCommunicationInterface;
use App\Entity\DeviceType;
use App\Form\DeviceCommunication\VpnContainerClientLogsType;
use App\Form\DeviceCommunication\VpnContainerClientRegisterType;
use App\Model\ResponseModel;
use App\Model\VpnContainerClientResponseModel;
use App\Service\ConfigurationManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait VpnContainerClientControllerTrait
{
    // abstract method forces base class to provide valid device communication class
    abstract protected function getDeviceCommunication(): VpnContainerClientCommunicationInterface;

    abstract protected function getDeviceType(): ?DeviceType;

    abstract protected function getConfigurationManager(): ConfigurationManager;

    abstract protected function preAction(Request $request, null|callable $customApplyDenyAccess = null): Response|ResponseModel|null;

    abstract protected function postAction(Request $request, Response|ResponseModel $response): ?Response;

    abstract protected function createForm(string $type, $data = null, array $options = []): FormInterface;

    // using custom method due to possible conflict with other traits (EdgeGatewayControllerTrait)
    public function applyDenyAccessVpnContainerClient(Request $request): Response|ResponseModel|null
    {
        if ($this->getConfigurationManager()->isMaintenanceModeEnabled()) {
            $response = new VpnContainerClientResponseModel();
            $response->setError('Under maintenance');

            return $response;
        }

        return null;
    }

    #[Rest\View(serializerGroups: ['vpnContainerClient:register', 'vpnContainerClient:configuration'])]
    public function registerAction(Request $request): Response|ResponseModel
    {
        $response = $this->preAction($request, function ($request) {return $this->applyDenyAccessVpnContainerClient($request); });
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
            ],
            $this->getDeviceCommunication()->getDeviceTypeValidationGroups($this->getDeviceType())
        );

        $form = $this->createForm(VpnContainerClientRegisterType::class, null, ['allow_extra_fields' => true, 'validation_groups' => $validationGroups]);
        $form->submit(array_merge($request->request->all(), $request->query->all()));

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->getDeviceCommunication()->processVpnContainerClientRegister($this->getDeviceType(), $request, $form->getData());
        } else {
            $response = $this->getDeviceCommunication()->prepareErrorResponse($this->getDeviceType(), $request, $form);
        }

        return $this->postAction($request, $response);
    }

    #[Rest\View(serializerGroups: ['vpnContainerClient:register', 'vpnContainerClient:configuration'])]
    public function configurationAction(Request $request, string $uuid): Response|ResponseModel
    {
        $response = $this->preAction($request, function ($request) {return $this->applyDenyAccessVpnContainerClient($request); });
        if ($response) {
            return $this->postAction($request, $response);
        }

        $response = $this->getDeviceCommunication()->processVpnContainerClientConfiguration($this->getDeviceType(), $request, $uuid);

        return $this->postAction($request, $response);
    }

    #[Rest\View(serializerGroups: ['vpnContainerClient:register', 'vpnContainerClient:configuration'])]
    public function sendLogsAction(Request $request, string $uuid): Response|ResponseModel
    {
        $response = $this->preAction($request, function ($request) {return $this->applyDenyAccessVpnContainerClient($request); });
        if ($response) {
            return $this->postAction($request, $response);
        }

        $validationGroups = \array_merge(
            [
                'Default',
                'vpnContainerClientLogs',
            ],
            $this->getDeviceCommunication()->getDeviceTypeValidationGroups($this->getDeviceType())
        );

        $form = $this->createForm(VpnContainerClientLogsType::class, null, ['allow_extra_fields' => true, 'validation_groups' => $validationGroups]);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->getDeviceCommunication()->processVpnContainerClientSendLogs($this->getDeviceType(), $request, $form->getData(), $uuid);
        } else {
            $response = $this->getDeviceCommunication()->prepareErrorResponse($this->getDeviceType(), $request, $form);
        }

        return $this->postAction($request, $response);
    }
}
