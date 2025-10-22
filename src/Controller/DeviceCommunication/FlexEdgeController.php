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

use App\DeviceCommunication\FlexEdgeCommunication;
use App\Entity\Config;
use App\Entity\Device;
use App\Entity\Firmware;
use App\Enum\Feature;
use App\Form\DeviceCommunication\FlexEdgeType;
use App\Service\Helper\ConfigManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FlexEdgeController extends AbstractDeviceController
{
    use EntityManagerTrait;
    use ConfigManagerTrait;

    protected function getDeviceCommunication(): FlexEdgeCommunication
    {
        // This should never happen, but testing it to make sure controller doesn't return 500 error
        if (!parent::getDeviceCommunication() instanceof FlexEdgeCommunication) {
            throw new NotFoundHttpException();
        }

        return parent::getDeviceCommunication();
    }

    public function updateStatusAction(Request $request): Response
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
                'flexEdgeCommunication',
            ],
            $this->getDeviceCommunication()->getDeviceTypeValidationGroups($this->getDeviceType())
        );

        $form = $this->createForm(FlexEdgeType::class, null, ['allow_extra_fields' => true, 'validation_groups' => $validationGroups]);

        $form->submit(array_merge($request->request->all(), $request->query->all()));

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->getDeviceCommunication()->process($this->getDeviceType(), $request, $form->getData());
        } else {
            $response = $this->getDeviceCommunication()->prepareErrorResponse($this->getDeviceType(), $request, $form);
        }

        return $this->postAction($request, $response);
    }

    /**
     * No security implemented for downloading.
     * Reasons:
     * - FlexEdge by default doesn't use any security
     * - During downloading uuid has to be provided that acts as password.
     */
    public function downloadFileAction(Request $request, string $fileName): Response
    {
        if ($response = $this->preAction($request)) {
            return $response;
        }

        $fileColnParts = explode(':', $fileName);
        if (!isset($fileColnParts[0])) {
            return new Response('Invalid filename');
        }
        $fileParts = explode('[[ID]]', $fileColnParts[0]);
        if (!isset($fileParts[1])) {
            return new Response('Invalid filename');
        }
        $fileSubParts = explode('.', $fileParts[1]);

        if (!isset($fileSubParts[0]) || !isset($fileSubParts[1])) {
            return new Response('Invalid filename');
        }
        if ('ci3' == $fileSubParts[1]) {
            // firmware
            $firmware = $this->getRepository(Firmware::class)->findOneBy(['uuid' => $fileSubParts[0], 'deviceType' => $this->getDeviceType()]);
            if (!$firmware) {
                return new Response('Firmware not found');
            }

            $file = $firmware->getUploadDir('file_path').'/'.$firmware->getFilename();

            return new BinaryFileResponse($file);
        }

        if ('zip' == $fileSubParts[1]) {
            // config
            $device = $this->getRepository(Device::class)->findOneBy(['uuid' => $fileSubParts[0], 'deviceType' => $this->getDeviceType()]);
            if (!$device) {
                return new Response('Config not found');
            }

            $this->configManager->setDeviceCommunication($this->getDeviceCommunication());
            $configDevice = $this->configManager->generateDeviceConfig($this->getDeviceType(), $device, Feature::PRIMARY, true);

            $config = $configDevice->isGenerated() ? $configDevice->getConfigGenerated() : null;

            return new Response($config);
        }

        return new Response('File not found');
    }
}
