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

use App\Entity\CommunicationLog;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Entity\Traits\CommunicationEntityInterface;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Enum\CertificateCategory;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\Feature;
use App\Enum\FieldRequirement;
use App\Form\DeviceCommunication\SgGatewayType;
use App\Model\ConfigDevice;
use App\Model\FieldRequirementsModel;
use App\Model\ResponseModel;
use App\Model\SerializableJson;
use App\Model\SgGatewayModel;
use App\Model\SgGatewayResponseModel;
use App\Model\VariableInterface;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\TranslatorTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class SgGatewayCommunication extends AbstractDeviceCommunication
{
    use ConfigurationManagerTrait;
    use TranslatorTrait;

    public const RESPONSE_CONFIGURATION_NOCHANGE = 'nochange';

    /**
     * @var ?SgGatewayModel
     */
    protected $sgGatewayModel;

    public function getSgGatewayModel(): ?SgGatewayModel
    {
        return $this->sgGatewayModel;
    }

    public function setSgGatewayModel(?SgGatewayModel $sgGatewayModel)
    {
        $this->sgGatewayModel = $sgGatewayModel;
    }

    public function getRoutes(DeviceType $deviceType): RouteCollection
    {
        $routeCollection = new RouteCollection();

        $path = $deviceType->getValidRoutePrefix().'/configuration';
        $defaults = [
                '_controller' => 'App\Controller\DeviceCommunication\SgGatewayController::configurationAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = 'sgGatewayConfiguration'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        return $routeCollection;
    }

    public function getCommunicationProcedureRequirementsOptional(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_CONFIG1,
            CommunicationProcedureRequirement::HAS_CERTIFICATES,
            CommunicationProcedureRequirement::HAS_VARIABLES,
            CommunicationProcedureRequirement::HAS_ENDPOINT_DEVICES,
            CommunicationProcedureRequirement::HAS_VPN,
        ];
    }

    public function getCommunicationProcedureRequirementsRequired(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_FIRMWARE1,
            CommunicationProcedureRequirement::HAS_TEMPLATES,
        ];
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryRequired
     */
    public function getCommunicationProcedureCertificateCategoryRequired(): array
    {
        return [];
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryOptional
     */
    public function getCommunicationProcedureCertificateCategoryOptional(): array
    {
        return [
            CertificateCategory::DEVICE_VPN,
            CertificateCategory::CUSTOM,
        ];
    }

    public function getCommunicationProcedureFieldsRequirements(): FieldRequirementsModel
    {
        $fieldRequirements = new FieldRequirementsModel();

        $fieldRequirements->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $fieldRequirements->setFieldModel(FieldRequirement::OPTIONAL);

        return $fieldRequirements;
    }

    public function isFirmwareSecured(): bool
    {
        return true;
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
        $sgGatewayModel = $this->executeJsonAuthenticatorForm($request, SgGatewayType::class, ['validation_groups' => 'authentication']);
        if (!$sgGatewayModel) {
            return null;
        }

        // Checking if serial number was provided as validation groups are not used
        if (!$sgGatewayModel->getSerialNumber()) {
            return null;
        }

        return $this->findDeviceByIdentifier($sgGatewayModel->getSerialNumber());
    }

    public function process(DeviceType $deviceType, Request $request, SgGatewayModel $sgGatewayModel): ResponseModel
    {
        $incrementConnections = false;
        $this->setDeviceType($deviceType);
        $this->setRequest($request);
        $this->setSgGatewayModel($sgGatewayModel);
        $this->setResponse(new SgGatewayResponseModel());
        $this->communicationLogManager->setRequest($this->getRequest());

        // check if DT is configured correctly for this communication procedure
        if (!$this->isCommunicationProcedureRequirementsSatisfied($this->getCommunicationProcedureRequirementsRequired())) {
            // detailed logs will be filled by method above
            $this->communicationLogManager->createLogCritical('log.communicationProcedureRequirementsNotSatisfied');
            // RESPONSE
            $this->getResponse()->setError('Device Type configuration error.');

            return $this->getResponse();
        }

        $this->setDevice($this->findDeviceByIdentifier($this->getSgGatewayModel()->getSerialNumber()));

        $this->communicationLogManager->createLogInfo('log.incomingRequest');

        $incrementConnections = false;

        if (!$this->getDevice()) {
            $this->processMissingSgGateway();
        } else {
            $this->communicationLogManager->setDevice($this->getDevice());
            $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
            $this->communicationLogManager->createLogDebug('log.deviceFound');
        }

        if (!$this->getDevice()) {
            // if processMissingSgGateway didn't create device finish communication procedure
            return $this->getResponse();
        }

        if ($this->processDeviceTypeMismatch()) {
            return $this->getResponse();
        }

        $this->processSgGateway();

        $incrementConnections = true;

        $this->communicationLogManager->createLogDebug('log.requestProcessed', [], $this->getDeviceModelResponse($this->getResponse())->__toString());

        $this->entityManager->flush();

        if ($incrementConnections) {
            $this->incrementDeviceConnections();
            $this->entityManager->flush();
        }

        return $this->getResponse();
    }

    protected function processMissingSgGateway()
    {
        $this->communicationLogManager->createLogInfo('log.deviceNotFound', ['identifier' => $this->getSgGatewayModel()->getSerialNumber()]);

        $this->createSgGateway();
    }

    /**
     * Processing existing Sg Gateway.
     */
    protected function processSgGateway()
    {
        $this->updateLastDataInformation();

        $this->getResponse()->setSerialNumber($this->getDevice()->getSerialNumber());

        if (!$this->getDevice()->getEnabled()) {
            $communicationLog = $this->communicationLogManager->createLogWarning('log.deviceDisabled', ['identifier' => $this->getSgGatewayModel()->getSerialNumber()]);
            // RESPONSE
            $this->getResponse()->setSerialNumber($this->getSgGatewayModel()->getSerialNumber());
            $this->getResponse()->setError($communicationLog->getMessage());

            return;
        }

        if (!$this->getDeviceTemplate()) {
            $this->communicationLogManager->createLogWarning('log.deviceTemplateMissing');
            // RESPONSE
            $this->handleNoChange();

            return;
        }

        if ($this->processFirmware(Feature::PRIMARY, $this->getSgGatewayModel()->getFirmwareVersion())) {
            $this->getDevice()->setReinstallFirmware1(true);
        }

        $reinstallingFirmware = $this->processReinstallFirmware(Feature::PRIMARY);

        if (!$reinstallingFirmware) {
            // Check if config will be sent if certificate or deviceSecret will renew or generate
            if ($this->getShouldReinstallConfig(feature: Feature::PRIMARY, createLogs: false, expectedReinstallConfigFlag: true)) {
                if ($this->processAutoRenewCertificates()) {
                    $this->getDevice()->setReinstallConfig1(true);
                }

                if ($this->processAutoGenerationOrRenewDeviceSecrets()) {
                    $this->getDevice()->setReinstallConfig1(true);
                }
            }

            $reinstallStartupConfig = $this->processReinstallConfig(Feature::PRIMARY);

            if (!$reinstallStartupConfig) {
                $this->handleNoChange();
            }
        }

        $this->entityManager->persist($this->getDevice());
    }

    protected function createSgGateway()
    {
        $this->setDevice(new Device());
        $this->getDevice()->setDeviceType($this->getDeviceType());
        $this->getDevice()->setVirtualSubnetCidr($this->getDeviceType()->getVirtualSubnetCidr());
        $this->getDevice()->setMasqueradeType($this->getDeviceType()->getMasqueradeType());
        $this->getDevice()->setName($this->getSgGatewayModel()->getSerialNumber());
        $this->getDevice()->setSerialNumber($this->getSgGatewayModel()->getSerialNumber());
        $this->getDevice()->setIdentifier($this->generateIdentifier($this->getDevice()));
        $this->getDevice()->setModel($this->getSgGatewayModel()->getHardwareVersion());
        $this->getDevice()->setUuid($this->getDeviceTypeUniqueUuid());
        $this->getDevice()->setHashIdentifier($this->getDeviceUniqueHashIdentifier());

        $this->communicationLogManager->setDevice($this->getDevice());
        $this->communicationLogManager->updateCommunicationLogsWithoutDevice();

        $this->communicationLogManager->createLogInfo('log.deviceCreate');

        $this->entityManager->persist($this->getDevice());

        $this->incrementDeviceConnections();
    }

    protected function handleNoChange(): void
    {
        $this->communicationLogManager->createLogInfo('log.deviceNoChange');
        $this->getResponse()->setSerialNumber($this->getDevice() ? $this->getDevice()->getSerialNumber() : $this->getSgGatewayModel()->getSerialNumber());
        $this->getResponse()->setConfiguration(self::RESPONSE_CONFIGURATION_NOCHANGE);
    }

    protected function handleDeviceTypeMismatch(CommunicationLog $communicationLog)
    {
        $this->getResponse()->setSerialNumber($this->getDevice() ? $this->getDevice()->getSerialNumber() : $this->getSgGatewayModel()->getSerialNumber());
        $this->getResponse()->setError($communicationLog->getMessage());
    }

    // this function is used in controller directly
    protected function handleErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form, array $messages, string $message): Response|ResponseModel
    {
        $response = new SgGatewayResponseModel();
        $response->setError($message);

        return $response;
    }

    protected function handleReinstallFirmware(Feature $feature, Firmware $firmware): void
    {
        $configuration = ['firmwareUrl' => $this->getFirmwareUrl($feature, $firmware)];
        $this->getResponse()->setConfiguration(new SerializableJson(\json_encode($configuration)));

        $this->getDevice()->setReinstallFirmware1(false);
        $this->entityManager->persist($this->getDevice());
    }

    protected function handleReinstallConfig(Feature $feature, ConfigDevice $generatedConfigDevice): void
    {
        // Read more about SerializableJson use case in App\Serializer\Normalizer\SerializableJsonNormalizer.
        $this->getResponse()->setConfiguration(new SerializableJson($generatedConfigDevice->getConfigGenerated()));

        $this->getDevice()->setReinstallConfig1(false);
        $this->entityManager->persist($this->getDevice());
        $this->entityManager->flush();
    }

    protected function updateLastDataInformation(): void
    {
        $this->fillCommunicationData($this->getDevice());
        $this->fillVersionFirmware1($this->getDevice());
    }

    protected function getCustomDeviceVariables(bool $createLogs = true): array
    {
        // Make sure to use nullsafe operators
        // if there is no Device, variables names should be still generated
        return [
            VariableInterface::VARIABLE_NAME_HARDWAREVERSION => $this->getDevice()?->getModel(),
            VariableInterface::VARIABLE_NAME_FIRMWAREVERSION => $this->getDevice()?->getFirmwareVersion1(),
        ];
    }

    public function fillCommunicationData(CommunicationEntityInterface $entity): CommunicationEntityInterface
    {
        if ($this->getSgGatewayModel()) {
            if ($this->getSgGatewayModel()->getSerialNumber()) {
                $entity->setSerialNumber($this->getSgGatewayModel()->getSerialNumber());
            }

            if ($this->getSgGatewayModel()->getHardwareVersion()) {
                $entity->setModel($this->getSgGatewayModel()->getHardwareVersion());
            }
        }

        parent::fillCommunicationData($entity);

        return $entity;
    }

    public function fillVersionFirmware1(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        if ($this->getSgGatewayModel()) {
            if ($this->getDeviceType() && $this->getDeviceType()->getHasFirmware1()) {
                $entity->setFirmwareVersion1($this->getSgGatewayModel()->getFirmwareVersion());
            }
        }

        return $entity;
    }
}
