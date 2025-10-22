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

namespace App\DeviceCommunication\Trait\Abstract;

use App\Entity\Device;
use App\Service\Helper\FormFactoryTrait;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

trait AbstractDeviceCommunicationSecurityTrait
{
    use FormFactoryTrait;

    /**
     * Method provides device by checking request - method is specifically used during authentication, before controller is executed.
     */
    public function getRequestedDevice(Request $request): ?Device
    {
        return null;
    }

    /**
     * Helper method executes POST data form and returns form data model or null if failed.
     */
    protected function executePostAuthenticatorForm(Request $request, string $type, array $options = [], bool $convertEncoding = true, bool $useQueryParameters = false): mixed
    {
        // cloning request parameters because they might be processed
        $requestParameters = new InputBag($request->request->all());

        if ($convertEncoding) {
            // When sending request with Content-Type = dls/diagnosedata $_POST variable is empty (passed data is not interpreted by default)
            // This results in empty data in request
            // This also applies to any Content-Type that is not considered POST Content-Type
            if (0 === $requestParameters->count()) {
                $parameters = [];
                mb_parse_str($request->getContent(), $parameters);
                $requestParameters->replace($parameters);
            }
        }

        $data = $requestParameters->all();

        if ($useQueryParameters) {
            $data = array_merge($data, $request->query->all());
        }

        return $this->executeAuthenticatorForm($type, $data, $options);
    }

    /**
     * Helper method executes JSON data form and returns form data model or null if failed.
     */
    protected function executeJsonAuthenticatorForm(Request $request, string $type, array $options = []): mixed
    {
        $data = json_decode($request->getContent(), true);

        return $this->executeAuthenticatorForm($type, $data, $options);
    }

    /**
     * Helper method executes form and returns form data model or null if failed.
     */
    protected function executeAuthenticatorForm(string $type, mixed $data = null, array $options = []): mixed
    {
        $form = $this->formFactory->create($type, null, \array_merge($options, ['allow_extra_fields' => true]));
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            return $form->getData();
        }

        return null;
    }
}
