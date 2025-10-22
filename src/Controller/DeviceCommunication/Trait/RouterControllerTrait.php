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

use App\DeviceCommunication\Trait\RouterCommunicationInterface;
use App\Entity\DeviceType;
use App\Form\DeviceCommunication\RouterType;
use App\Model\ResponseModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait RouterControllerTrait
{
    // abstract method forces base class to provide valid device communication class
    abstract protected function getDeviceCommunication(): RouterCommunicationInterface;

    abstract protected function getDeviceType(): ?DeviceType;

    abstract protected function preAction(Request $request, null|callable $customApplyDenyAccess = null): Response|ResponseModel|null;

    abstract protected function postAction(Request $request, Response|ResponseModel $response): ?Response;

    abstract protected function createForm(string $type, $data = null, array $options = []): FormInterface;

    public function configAction(Request $request): Response
    {
        if ($response = $this->preAction($request)) {
            return $response;
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
                'router',
            ],
            $this->getDeviceCommunication()->getDeviceTypeValidationGroups($this->getDeviceType())
        );

        $form = $this->createForm(RouterType::class, null, ['validation_groups' => $validationGroups]);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->getDeviceCommunication()->process($this->getDeviceType(), $request, $form->getData());
        } else {
            $response = $this->getDeviceCommunication()->prepareErrorResponse($this->getDeviceType(), $request, $form);
        }

        return $this->postAction($request, $response);
    }
}
