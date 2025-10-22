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

namespace App\DeviceCommunication;

use App\DeviceCommunication\Trait\EdgeGatewayCommunicationInterface;
use App\DeviceCommunication\Trait\EdgeGatewayCommunicationTrait;
use App\DeviceCommunication\Trait\VpnContainerClientCommunicationInterface;
use App\DeviceCommunication\Trait\VpnContainerClientCommunicationTrait;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\CertificateCategory;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\FieldRequirement;
use App\Model\EdgeGatewayResponseModel;
use App\Model\FieldRequirementsModel;
use App\Model\ResponseModel;
use App\Model\VpnContainerClientResponseModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;

class EdgeGatewayWithVpnContainerClientCommunication extends AbstractDeviceCommunication implements EdgeGatewayCommunicationInterface, VpnContainerClientCommunicationInterface
{
    use EdgeGatewayCommunicationTrait {
        EdgeGatewayCommunicationTrait::getRoutes as getRoutesEdgeGateway;
        EdgeGatewayCommunicationTrait::getRequestedDevice as getRequestedDeviceEdgeGateway;
        EdgeGatewayCommunicationTrait::getCommunicationProcedureRequirementsOptional as getCommunicationProcedureRequirementsOptionalEdgeGateway;
        EdgeGatewayCommunicationTrait::getCommunicationProcedureRequirementsRequired as getCommunicationProcedureRequirementsRequiredEdgeGateway;
        EdgeGatewayCommunicationTrait::getCommunicationProcedureFieldsRequirements as getCommunicationProcedureFieldsRequirementsEdgeGateway;
        EdgeGatewayCommunicationTrait::generateIdentifier as generateIdentifierEdgeGateway;
        // EdgeGatewayCommunicationTrait::handleDeviceTypeMismatch as handleDeviceTypeMismatchEdgeGateway;
        EdgeGatewayCommunicationTrait::handleErrorResponse as handleErrorResponseEdgeGateway;
    }
    use VpnContainerClientCommunicationTrait {
        VpnContainerClientCommunicationTrait::getRoutes as getRoutesVpnContainerClient;
        VpnContainerClientCommunicationTrait::getRequestedDevice as getRequestedDeviceVpnContainerClient;
        VpnContainerClientCommunicationTrait::getCommunicationProcedureRequirementsOptional as getCommunicationProcedureRequirementsOptionalVpnContainerClient;
        VpnContainerClientCommunicationTrait::getCommunicationProcedureRequirementsRequired as getCommunicationProcedureRequirementsRequiredVpnContainerClient;
        VpnContainerClientCommunicationTrait::getCommunicationProcedureFieldsRequirements as getCommunicationProcedureFieldsRequirementsVpnContainerClient;
        VpnContainerClientCommunicationTrait::generateIdentifier as generateIdentifierVpnContainerClient;
        // VpnContainerClientCommunicationTrait::handleDeviceTypeMismatch as handleDeviceTypeMismatchVpnContainerClient;
        VpnContainerClientCommunicationTrait::handleErrorResponse as handleErrorResponseVpnContainerClient;
        VpnContainerClientCommunicationTrait::processMissingVpnContainerClient as processMissingVpnContainerClientVpnContainerClient;
    }

    protected string $controllerName = 'App\Controller\DeviceCommunication\EdgeGatewayWithVpnContainerClientController';
    protected string $routePrefix = 'edgeGatewayWithVpnContainerClient';

    public function getRoutes(DeviceType $deviceType): RouteCollection
    {
        $routeCollection = new RouteCollection();

        $routeCollection->addCollection($this->getRoutesEdgeGateway($deviceType, $this->controllerName, $this->routePrefix));
        $routeCollection->addCollection($this->getRoutesVpnContainerClient($deviceType, $this->controllerName, $this->routePrefix));

        return $routeCollection;
    }

    public function getCommunicationProcedureRequirementsOptional(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_FIRMWARE1,
            CommunicationProcedureRequirement::HAS_CONFIG1,
            CommunicationProcedureRequirement::HAS_ENDPOINT_DEVICES,
        ];
    }

    public function getCommunicationProcedureRequirementsRequired(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_VARIABLES,
            CommunicationProcedureRequirement::HAS_MASQUERADE,
            CommunicationProcedureRequirement::HAS_CERTIFICATES,
            CommunicationProcedureRequirement::HAS_GSM,
            CommunicationProcedureRequirement::HAS_VPN,
            CommunicationProcedureRequirement::HAS_REQUEST_CONFIG,
            CommunicationProcedureRequirement::HAS_TEMPLATES,
            CommunicationProcedureRequirement::HAS_DEVICE_COMMANDS,
        ];
    }

    public function getCommunicationProcedureFieldsRequirements(): FieldRequirementsModel
    {
        $fieldRequirements = new FieldRequirementsModel();

        $fieldRequirements->setFieldSerialNumber(FieldRequirement::REQUIRED);

        $fieldRequirements->setFieldModel(FieldRequirement::OPTIONAL);
        $fieldRequirements->setFieldRegistrationId(FieldRequirement::OPTIONAL);
        $fieldRequirements->setFieldEndorsementKey(FieldRequirement::OPTIONAL);
        $fieldRequirements->setFieldHardwareVersion(FieldRequirement::OPTIONAL);

        return $fieldRequirements;
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryRequired
     */
    public function getCommunicationProcedureCertificateCategoryRequired(): array
    {
        return [
            CertificateCategory::DEVICE_VPN,
        ];
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryOptional
     */
    public function getCommunicationProcedureCertificateCategoryOptional(): array
    {
        return [
            CertificateCategory::DPS,
            CertificateCategory::EDGE_CA,
            CertificateCategory::CUSTOM,
        ];
    }

    public function generateIdentifier(Device $device): string
    {
        if ($device->getSerialNumber()) {
            return $device->getSerialNumber();
        }

        return parent::generateIdentifier($device);
    }

    /**
     * Method provides device by checking request - method is specifically used during authentication, before controller is executed.
     */
    public function getRequestedDevice(Request $request): ?Device
    {
        $device = $this->getRequestedDeviceEdgeGateway($request);
        if ($device) {
            return $device;
        }

        return $this->getRequestedDeviceVpnContainerClient($request);
    }

    // Method is separated from main flow to allow easier override especially in EdgeGatewayWithVpnContainerClientCommunication
    // This override disables auto generation and renewal of device secrets for VPN Container Client /configuration/{uuid} endpoint
    public function processVpnContainerClientAutoGenerationOrRenewDeviceSecrets(): void
    {
    }

    protected function processMissingVpnContainerClient()
    {
        if ($this->getVpnContainerClientModel()->getName()) {
            $this->setDevice($this->findDeviceByIdentifier($this->getVpnContainerClientModel()->getName()));
            if (!$this->getDevice()) {
                $this->processMissingVpnContainerClientVpnContainerClient();
            } else {
                $this->communicationLogManager->setDevice($this->getDevice());
                $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
                $this->communicationLogManager->createLogDebug('log.deviceFound');
            }
        } else {
            $this->processMissingVpnContainerClientVpnContainerClient();
        }
    }

    protected function handleErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form, array $messages, string $message): Response|ResponseModel
    {
        $routeName = $request->get('_route');

        if ($routeName) {
            $routeCollection = $this->getRoutesVpnContainerClient($deviceType, $this->controllerName, $this->routePrefix);
            if ($routeCollection->get($routeName)) {
                $response = new VpnContainerClientResponseModel();
                $response->setError($message);

                return $response;
            }
        }

        $response = new EdgeGatewayResponseModel();
        $response->setError($message);

        return $response;
    }
}
