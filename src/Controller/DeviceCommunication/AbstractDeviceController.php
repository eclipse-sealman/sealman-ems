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

use App\DeviceCommunication\DeviceCommunicationInterface;
use App\Entity\DeviceType;
use App\Model\ResponseModel;
use App\Service\ConfigurationManager;
use App\Service\Helper\CommunicationLogManagerTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\SystemUserTrait;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractDeviceController extends AbstractFOSRestController
{
    use DeviceCommunicationFactoryTrait;
    use ConfigurationManagerTrait;
    use CommunicationLogManagerTrait;
    use SystemUserTrait;
    use EntityManagerTrait;

    /**
     * @var ?DeviceType
     */
    protected $deviceType;

    protected function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    /**
     * @var ?DeviceCommunicationInterface
     */
    protected $deviceCommunication;

    protected function getDeviceCommunication(): ?DeviceCommunicationInterface
    {
        if (!$this->getDeviceType()) {
            return null;
        }

        if (!$this->deviceCommunication) {
            $this->deviceCommunication = $this->deviceCommunicationFactory->getDeviceCommunicationByDeviceType($this->getDeviceType());
            $this->deviceCommunication->setDeviceType($this->getDeviceType());
        }

        return $this->deviceCommunication;
    }

    protected function getConfigurationManager(): ConfigurationManager
    {
        return $this->configurationManager;
    }

    protected function applyDenyAccess(Request $request): Response|ResponseModel|null
    {
        if ($this->configurationManager->isMaintenanceModeEnabled()) {
            return new Response('Under maintenance');
        }

        return null;
    }

    protected function preAction(Request $request, null|callable $customApplyDenyAccess = null): Response|ResponseModel|null
    {
        $this->deviceType = $this->deviceCommunicationFactory->getRequestedDeviceType($request);
        if (!$this->getDeviceType()) {
            throw new NotFoundHttpException();
        }

        if (!$this->getDeviceCommunication()) {
            throw new NotFoundHttpException();
        }

        $this->denyAccessUnlessGranted('ROLE_DEVICE_TYPE_'.$this->getDeviceType()->getId());

        $this->communicationLogManager->setRequest($request);
        $this->communicationLogManager->setDeviceType($this->getDeviceType());
        $this->communicationLogManager->setDeviceCommunication($this->getDeviceCommunication());

        if (!$this->getUser()) {
            if (!$this->getSystemUser()) {
                return $this->prepareSystemUserDoesNotExistResponse($request);
            }
        }

        $this->createDeviceRequestIncoming($request);

        if (null !== $customApplyDenyAccess) {
            if ($response = $customApplyDenyAccess($request)) {
                return $response;
            }
        } else {
            if ($response = $this->applyDenyAccess($request)) {
                return $response;
            }
        }

        return null;
    }

    protected function postAction(Request $request, Response|ResponseModel $response): ?Response
    {
        if ($response instanceof Response) {
            $this->createDeviceResponseOutgoing($request, $response);
        }
        if ($response instanceof ResponseModel) {
            $response = $this->createDeviceResponseViewOutgoing($request, $response);
        }

        $this->entityManager->flush();
        $this->communicationLogManager->fillLogsWithAccessTags();
        $this->entityManager->flush();

        return $response;
    }

    protected function prepareSystemUserDoesNotExistResponse(Request $request): Response|ResponseModel
    {
        $this->communicationLogManager->createSystemUserDoesNotExist($request);
        $this->entityManager->flush();

        $communicationProcedure = $this->getDeviceCommunication();
        if ($communicationProcedure && \method_exists($communicationProcedure, 'prepareSystemUserDoesNotExistResponse')) {
            return $communicationProcedure->prepareSystemUserDoesNotExistResponse($request);
        }

        return new Response('System user does not exist');
    }

    protected function createDeviceRequestIncoming(Request $request): void
    {
        $communicationProcedure = $this->getDeviceCommunication();
        if ($communicationProcedure && \method_exists($communicationProcedure, 'createDeviceRequestIncoming')) {
            $communicationProcedure->createDeviceRequestIncoming($request);
            $this->entityManager->flush();

            return;
        }
        $this->communicationLogManager->createDeviceRequestIncoming($request);
        $this->entityManager->flush();
    }

    protected function createDeviceResponseOutgoing(Request $request, Response $response): void
    {
        $communicationProcedure = $this->getDeviceCommunication();
        if ($communicationProcedure && \method_exists($communicationProcedure, 'createDeviceResponseOutgoing')) {
            $communicationProcedure->createDeviceResponseOutgoing($response);
            $this->entityManager->flush();

            return;
        }
        $this->communicationLogManager->createDeviceResponseOutgoing($response);
        $this->entityManager->flush();
    }

    protected function createDeviceResponseViewOutgoing(Request $request, ResponseModel $response): Response
    {
        $communicationProcedure = $this->getDeviceCommunication();
        if ($communicationProcedure && \method_exists($communicationProcedure, 'createDeviceResponseViewOutgoing')) {
            $httpResponse = $communicationProcedure->createDeviceResponseViewOutgoing($response);
        } else {
            $httpResponse = $this->communicationLogManager->createDeviceResponseViewOutgoing($response);
        }

        $this->entityManager->flush();

        return $httpResponse;
    }
}
