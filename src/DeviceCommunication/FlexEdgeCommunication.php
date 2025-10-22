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

use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Entity\Traits\CommunicationEntityInterface;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Enum\CertificateCategory;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\Feature;
use App\Enum\FieldRequirement;
use App\Form\DeviceCommunication\FlexEdgeType;
use App\Model\ConfigDevice;
use App\Model\FieldRequirementsModel;
use App\Model\FlexEdgeModel;
use App\Model\ResponseModel;
use App\Model\VariableInterface;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\VpnManagerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FlexEdgeCommunication extends AbstractDeviceCommunication
{
    use ConfigurationManagerTrait;
    use TranslatorTrait;
    use VpnManagerTrait;

    /**
     * @var ?FlexEdgeModel
     */
    protected $flexEdgeModel;

    public function getFlexEdgeModel(): ?FlexEdgeModel
    {
        return $this->flexEdgeModel;
    }

    public function setFlexEdgeModel(?FlexEdgeModel $flexEdgeModel)
    {
        $this->flexEdgeModel = $flexEdgeModel;
    }

    public function getRoutes(DeviceType $deviceType): RouteCollection
    {
        $routeCollection = new RouteCollection();

        $path = $deviceType->getValidRoutePrefix().'/jbm_mgmt/update_status.php';
        $defaults = [
                '_controller' => 'App\Controller\DeviceCommunication\FlexEdgeController::updateStatusAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST', 'GET'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = 'flexEdgeUpateStatus'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        $path = $deviceType->getValidRoutePrefix().'/downloader.php/{fileName}';
        $defaults = [
                '_controller' => 'App\Controller\DeviceCommunication\FlexEdgeController::downloadFileAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['GET'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = 'flexEdgeDownloadFile'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        return $routeCollection;
    }

    public function getCommunicationProcedureRequirementsOptional(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_CERTIFICATES,
            CommunicationProcedureRequirement::HAS_ENDPOINT_DEVICES,
            CommunicationProcedureRequirement::HAS_VARIABLES,
            CommunicationProcedureRequirement::HAS_VPN,
        ];
    }

    public function getCommunicationProcedureRequirementsRequired(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_FIRMWARE1,
            CommunicationProcedureRequirement::HAS_CONFIG1,
            CommunicationProcedureRequirement::HAS_TEMPLATES,
        ];
    }

    public function getCommunicationProcedureFieldsRequirements(): FieldRequirementsModel
    {
        $fieldRequirements = new FieldRequirementsModel();

        $fieldRequirements->setFieldSerialNumber(FieldRequirement::REQUIRED);
        $fieldRequirements->setFieldModel(FieldRequirement::REQUIRED_IN_COMMUNICATION);

        return $fieldRequirements;
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

    public function generateIdentifier(Device $device): string
    {
        if ($device->getSerialNumber()) {
            return $device->getSerialNumber();
        }

        return parent::generateIdentifier($device);
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getOrderedListOfPredefinedVariablesNames
     */
    protected function getOrderedListOfPredefinedVariablesNames(): ?array
    {
        if (!$this->getDeviceType()) {
            return [];
        }

        if ($this->getDeviceType()->getHasVariables()) {
            $variableNames = [
                VariableInterface::VARIABLE_NAME_SERIAL,
                VariableInterface::VARIABLE_NAME_NAME,
                VariableInterface::VARIABLE_NAME_SOURCEIP,
                VariableInterface::VARIABLE_NAME_XFORWARDEDFORIP,
                VariableInterface::VARIABLE_NAME_VPN_IP,
            ];

            if ($this->getDeviceType()->getIsEndpointDevicesAvailable()) {
                $variableNames = array_merge($variableNames, [
                    VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_ARRAY,
                    VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_VIRTUAL_IP_HOST_PART_ARRAY,
                    VariableInterface::VARIABLE_NAME_ENDPOINT_DEVICE_PHYSICAL_IP_ARRAY,
                    VariableInterface::VARIABLE_NAME_VIP_PREFIX,
                    VariableInterface::VARIABLE_NAME_PIP_PREFIX,
                ]);
            }

            if ($this->getDeviceType()->getIsVpnAvailable()) {
                $variableNames = array_merge($variableNames, [
                    VariableInterface::VARIABLE_NAME_VPN_IP,
                ]);
            }

            if ($this->getDeviceType()->getHasCertificates()) {
                $certificateVariableNames = $this->getDeviceTypeCertificateVariableNames([
                    VariableInterface::VARIABLE_NAME_CERTIFICATE_PLAIN,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PLAIN,
                    VariableInterface::VARIABLE_NAME_CA_PLAIN,
                    VariableInterface::VARIABLE_NAME_ROOT_CA_PLAIN,
                ]);

                $variableNames = array_merge($variableNames, $certificateVariableNames);
            }

            $variableNames[] = VariableInterface::VARIABLE_NAME_ENCODEDVPNCONFIG;

            return $variableNames;
        }

        return [];
    }

    /**
     * Method provides device by checking request - method is specifically used during authentication, before controller is executed.
     */
    public function getRequestedDevice(Request $request): ?Device
    {
        $flexEdgeModel = $this->executePostAuthenticatorForm($request, FlexEdgeType::class, ['validation_groups' => 'authentication'], true, true);
        if (!$flexEdgeModel) {
            return null;
        }

        // Checking if sn was provided as validation groups are not used
        if (!$flexEdgeModel->getSn()) {
            return null;
        }

        return $this->findDeviceByIdentifier($flexEdgeModel->getSn());
    }

    public function process(DeviceType $deviceType, Request $request, FlexEdgeModel $flexEdgeModel): Response
    {
        $incrementConnections = false;
        $this->setDeviceType($deviceType);
        $this->setRequest($request);
        $this->setFlexEdgeModel($flexEdgeModel);
        $this->setResponse(new Response());
        $this->communicationLogManager->setRequest($this->getRequest());

        // check if DT is configured correctly for this communication procedure
        if (!$this->isCommunicationProcedureRequirementsSatisfied($this->getCommunicationProcedureRequirementsRequired())) {
            // detailed logs will be filled by method above
            $this->communicationLogManager->createLogCritical('log.communicationProcedureRequirementsNotSatisfied');
            // RESPONSE
            $this->getResponse()->setContent('Device Type configuration error.');

            $this->communicationLogManager->clearRequest();

            return $this->getResponse();
        }

        if ($this->getFlexEdgeModel()->getFileId()) {
            return $this->processFileStatusUpdate();
        }

        $this->setDevice($this->findDeviceByIdentifier($this->getFlexEdgeModel()->getSn()));

        $this->communicationLogManager->createLogInfo('log.incomingRequest');

        $incrementConnections = false;

        if (!$this->getDevice()) {
            $this->processMissingFlexEdge();
        } else {
            $this->communicationLogManager->setDevice($this->getDevice());
            $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
            $this->communicationLogManager->createLogDebug('log.deviceFound');
        }

        if (!$this->getDevice()) {
            // if processMissingFlexEdge didn't create device finish communication procedure
            return $this->getResponse();
        }

        if ($this->processDeviceTypeMismatch()) {
            return $this->getResponse();
        }

        $this->processFlexEdge();

        $incrementConnections = true;

        $this->communicationLogManager->createLogDebug('log.requestProcessed', [], $this->getResponse()->__toString());

        $this->entityManager->flush();

        if ($incrementConnections) {
            $this->incrementDeviceConnections();
            $this->entityManager->flush();
        }

        $this->communicationLogManager->clearRequest();

        return $this->getResponse();
    }

    protected function processFileStatusUpdate(): Response
    {
        $this->setDevice($this->findDeviceByIdentifier($this->getFlexEdgeModel()->getSn()));

        if (!$this->getDevice()) {
            $this->communicationLogManager->createLogError('log.flexEdgeDeviceFileUpdateNotFound', ['identifier' => $this->getFlexEdgeModel()->getSn()]);

            return new Response('ERROR: Device not found');
        }

        $this->communicationLogManager->setDevice($this->getDevice());
        $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
        $this->communicationLogManager->createLogDebug('log.deviceFound');

        $this->communicationLogManager->createLogInfo(
            'log.flexEdgeDeviceFileUpdate',
            [
                'identifier' => $this->getFlexEdgeModel()->getSn(),
                'fileId' => $this->getFlexEdgeModel()->getFileId(),
                'fileStatus' => $this->getFlexEdgeModel()->getFileStatus(),
            ]
        );

        return new Response();
    }

    protected function processMissingFlexEdge()
    {
        $this->communicationLogManager->createLogInfo('log.deviceNotFound', ['identifier' => $this->getFlexEdgeModel()->getSn()]);

        $this->createFlexEdge();
    }

    /**
     * Processing existing Flex Edge.
     */
    protected function processFlexEdge()
    {
        $this->updateLastDataInformation();

        if (!$this->getDevice()->getEnabled()) {
            $this->communicationLogManager->createLogWarning('log.deviceDisabled');
            // RESPONSE
            $this->getResponse()->setContent('ERROR: Flex edge device is disabled');

            return;
        }

        if (!$this->getDeviceTemplate()) {
            $this->communicationLogManager->createLogWarning('log.deviceTemplateMissing');
            // RESPONSE
            $this->getResponse()->setContent('ERROR: Flex edge device has no template assigned');

            return;
        }

        if ($this->getFlexEdgeModel()->getVer() && $this->processFirmware(Feature::PRIMARY, $this->getFlexEdgeModel()->getVer())) {
            $this->getDevice()->setReinstallFirmware1(true);
        }

        $reinstallingFirmware = $this->processReinstallFirmware(Feature::PRIMARY);

        if (!$reinstallingFirmware) {
            // Check if config will be sent if certificate or deviceSecret will renew or generate
            // Assuming no authentication for FlexEdge config download - which will not lockout device on new secret or certificate generation
            // Also FlexEdge is to be deprecated
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
                $this->communicationLogManager->createLogInfo('log.deviceNoConfigWillBeSent');
                $this->getResponse()->setContent(''); // Returning empty response
            }
        }

        $this->entityManager->persist($this->getDevice());
    }

    protected function createFlexEdge()
    {
        $this->setDevice(new Device());
        $this->getDevice()->setDeviceType($this->getDeviceType());
        $this->getDevice()->setVirtualSubnetCidr($this->getDeviceType()->getVirtualSubnetCidr());
        $this->getDevice()->setMasqueradeType($this->getDeviceType()->getMasqueradeType());
        $this->getDevice()->setName($this->getFlexEdgeModel()->getSn());
        $this->getDevice()->setSerialNumber($this->getFlexEdgeModel()->getSn());
        $this->getDevice()->setModel($this->getModel());
        $this->getDevice()->setUuid($this->getDeviceTypeUniqueUuid());
        $this->getDevice()->setHashIdentifier($this->getDeviceUniqueHashIdentifier());
        $this->getDevice()->setIdentifier($this->generateIdentifier($this->getDevice()));

        $this->communicationLogManager->setDevice($this->getDevice());
        $this->communicationLogManager->updateCommunicationLogsWithoutDevice();

        $this->communicationLogManager->createLogInfo('log.deviceCreate');

        $this->entityManager->persist($this->getDevice());

        $this->incrementDeviceConnections();
    }

    protected function getModel(): string
    {
        if ('DA50' == $this->getFlexEdgeModel()->getMn()) {
            return $this->getFlexEdgeModel()->getMn();
        }
        if ('DA70' == $this->getFlexEdgeModel()->getMn()) {
            return $this->getFlexEdgeModel()->getMn();
        }

        $this->communicationLogManager->createLogError('log.flexEdgeModelNotRecognized', ['receivedModel' => $this->getFlexEdgeModel()->getMn()]);

        return 'N/A';
    }

    // this function is used in controller directly
    protected function handleErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form, array $messages, string $message): Response|ResponseModel
    {
        $response = new Response($message);

        return $response;
    }

    protected function handleAutoRenewCertificate()
    {
        if ($this->isCertificateVariableUsedInDeviceConfig(Feature::PRIMARY)) {
            $this->communicationLogManager->createLogInfo('log.deviceForceReinstallConfig1');
            $this->getDevice()->setReinstallConfig1(true);
            $this->entityManager->persist($this->getDevice());
        }
    }

    protected function handleReinstallConfig(Feature $feature, ConfigDevice $generatedConfigDevice): void
    {
        $this->getDevice()->setReinstallConfig1(false);

        $fileNameId = $this->getDevice()->getUuid();

        $config = $generatedConfigDevice->isGenerated() ? $generatedConfigDevice->getConfigGenerated() : null;
        $contentSize = strlen($config);
        $contentMd5 = md5($config);
        $fileID = substr(md5(' T '.time()), 0, 24);

        $responseContent = 'DLFILE:'.$this->getDeviceType()->getValidRoutePrefix().'/downloader.php/JSON_DA50A[[ID]]'.$fileNameId.'.zip:'.$contentSize.':cfg:'.$contentMd5.':'.$fileID."\n";

        $this->communicationLogManager->createLogDebug('log.flexEdgeConfigResponses', ['fileId' => $fileID]);

        $this->entityManager->persist($this->getDevice());

        $this->getResponse()->setContent($responseContent);
    }

    protected function handleReinstallFirmware(Feature $feature, Firmware $firmware): void
    {
        $file = $firmware->getUploadDir('file_path').'/'.$firmware->getFilename();

        $contentSize = filesize($file);
        $contentMd5 = md5_file($file);

        $fileID = substr(md5(' T '.time()), 0, 24);

        $responseContent = 'DLFILE:'.$this->getDeviceType()->getValidRoutePrefix().'/downloader.php/DA50A_image[[ID]]'.$firmware->getUuid().'.ci3:'.$contentSize.':cfg:'.$contentMd5.':'.$fileID."\n";

        $this->getResponse()->setContent($responseContent);

        $this->getDevice()->setReinstallFirmware1(false);
        $this->entityManager->persist($this->getDevice());
    }

    protected function updateLastDataInformation(): void
    {
        $this->fillCommunicationData($this->getDevice());
        $this->fillVersionFirmware1($this->getDevice());
    }

    public function fillCommunicationData(CommunicationEntityInterface $entity): CommunicationEntityInterface
    {
        if ($this->getFlexEdgeModel()) {
            if ($this->getFlexEdgeModel()->getMn()) {
                $entity->setModel($this->getFlexEdgeModel()->getMn());
            }

            if ($this->getFlexEdgeModel()->getSn()) {
                $entity->setSerialNumber($this->getFlexEdgeModel()->getSn());
            }
        }

        return parent::fillCommunicationData($entity);
    }

    public function fillVersionFirmware1(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        if ($this->getFlexEdgeModel()) {
            if ($this->getDeviceType() && $this->getDeviceType()->getHasFirmware1()) {
                $entity->setFirmwareVersion1($this->getFlexEdgeModel()->getVer());
            }
        }

        return $entity;
    }

    public function getCustomDeviceVariables(bool $createLogs = true): array
    {
        $deviceVariables = [];

        $deviceVariables[VariableInterface::VARIABLE_NAME_ENCODEDVPNCONFIG] = null;

        if ($this->getDevice() && $this->getDeviceType()->getIsVpnAvailable()) {
            $vpnConfiguration = $this->vpnManager->generateConfiguration($this->getDevice());
            if ($vpnConfiguration) {
                $deviceVariables[VariableInterface::VARIABLE_NAME_ENCODEDVPNCONFIG] = base64_encode($vpnConfiguration);
            }
        }

        return $deviceVariables;
    }
}
