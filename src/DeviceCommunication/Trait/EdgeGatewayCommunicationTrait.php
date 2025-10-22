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

namespace App\DeviceCommunication\Trait;

use App\Entity\Device;
use App\Entity\DeviceCommand;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Entity\Traits\CommunicationEntityInterface;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Entity\Traits\GsmEntityInterface;
use App\Enum\CertificateCategory;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\ConfigFormat;
use App\Enum\DeviceCommandStatus;
use App\Enum\EdgeGatewayCommandName;
use App\Enum\EdgeGatewayCommandStatus;
use App\Enum\Feature;
use App\Enum\FieldRequirement;
use App\Form\DeviceCommunication\EdgeGatewayConfigurationAuthenticationType;
use App\Model\ConfigDevice;
use App\Model\EdgeGatewayModel;
use App\Model\EdgeGatewayResponseModel;
use App\Model\FieldRequirementsModel;
use App\Model\ResponseModel;
use App\Model\SerializableJson;
use App\Model\VariableInterface;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\VpnLogManagerTrait;
use App\Service\Helper\VpnManagerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

trait EdgeGatewayCommunicationTrait
{
    use ConfigurationManagerTrait;
    use VpnLogManagerTrait;
    use VpnManagerTrait;
    use TranslatorTrait;

    /**
     * @var ?EdgeGatewayModel
     */
    protected $edgeGatewayModel;

    public function getEdgeGatewayModel(): ?EdgeGatewayModel
    {
        return $this->edgeGatewayModel;
    }

    public function setEdgeGatewayModel(?EdgeGatewayModel $edgeGatewayModel)
    {
        $this->edgeGatewayModel = $edgeGatewayModel;
    }

    public function isFirmwareSecured(): bool
    {
        return true;
    }

    // This method assumes use of EdgeGatewayControllerTrait as controller
    public function getRoutes(
        DeviceType $deviceType,
        string $controllerName = 'App\Controller\DeviceCommunication\EdgeGatewayController',
        string $routePrefix = 'edgeGateway'): RouteCollection
    {
        $routeCollection = new RouteCollection();

        $path = $deviceType->getValidRoutePrefix().'/configuration';
        $defaults = [
                '_controller' => $controllerName.'::edgeGatewayConfigurationAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = $routePrefix.'Config'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        return $routeCollection;
    }

    public function getCommunicationProcedureRequirementsOptional(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_FIRMWARE1,
            CommunicationProcedureRequirement::HAS_CONFIG1,
            CommunicationProcedureRequirement::HAS_CERTIFICATES,
            CommunicationProcedureRequirement::HAS_ENDPOINT_DEVICES,
            CommunicationProcedureRequirement::HAS_VARIABLES,
            CommunicationProcedureRequirement::HAS_VPN,
        ];
    }

    public function getCommunicationProcedureRequirementsRequired(): array
    {
        return [
            CommunicationProcedureRequirement::HAS_REQUEST_CONFIG,
            CommunicationProcedureRequirement::HAS_TEMPLATES,
            CommunicationProcedureRequirement::HAS_DEVICE_COMMANDS,
            CommunicationProcedureRequirement::HAS_GSM,
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
        return [];
    }

    /**
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getCommunicationProcedureCertificateCategoryOptional
     */
    public function getCommunicationProcedureCertificateCategoryOptional(): array
    {
        return [
            CertificateCategory::DEVICE_VPN,
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
     * @see App\DeviceCommunication\AbstractDeviceCommunication::getOrderedListOfPredefinedVariablesNames
     */
    protected function getOrderedListOfPredefinedVariablesNames(): ?array
    {
        if (!$this->getDeviceType()) {
            return [];
        }

        if ($this->getDeviceType()->getHasVariables()) {
            $variableNames = [
                VariableInterface::VARIABLE_NAME_SERIALNUMBER,
                VariableInterface::VARIABLE_NAME_NAME,
                VariableInterface::VARIABLE_NAME_HARDWAREVERSION,
                VariableInterface::VARIABLE_NAME_FIRMWAREVERSION,
                VariableInterface::VARIABLE_NAME_REGISTRATIONID,
                VariableInterface::VARIABLE_NAME_ENDORSEMENTKEY,
                VariableInterface::VARIABLE_NAME_SOURCEIP,
                VariableInterface::VARIABLE_NAME_XFORWARDEDFORIP,
            ];

            if ($this->getDeviceType()->getHasCertificates()) {
                $certificateVariableNames = $this->getDeviceTypeCertificateVariableNames([
                    VariableInterface::VARIABLE_NAME_CERTIFICATE_PLAIN,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PLAIN,
                    VariableInterface::VARIABLE_NAME_CA_PLAIN,
                    VariableInterface::VARIABLE_NAME_ROOT_CA_PLAIN,
                ]);

                $variableNames = array_merge($variableNames, $certificateVariableNames);
            }

            return $variableNames;
        }

        return [];
    }

    /**
     * Method provides device by checking request - method is specifically used during authentication, before controller is executed.
     */
    public function getRequestedDevice(Request $request): ?Device
    {
        $edgeGatewayModel = $this->executeJsonAuthenticatorForm($request, EdgeGatewayConfigurationAuthenticationType::class, ['validation_groups' => 'authentication']);
        if (!$edgeGatewayModel) {
            return null;
        }

        // Checking if serial number was provided as validation groups are not used
        if (!$edgeGatewayModel->getSerialNumber()) {
            return null;
        }

        return $this->findDeviceByIdentifier($edgeGatewayModel->getSerialNumber());
    }

    protected function initilizeEdgeGatewayEndpoint(DeviceType $deviceType, Request $request, ?EdgeGatewayModel $edgeGatewayModel): Response|ResponseModel|null
    {
        $this->setDeviceType($deviceType);
        $this->setRequest($request);
        $this->setEdgeGatewayModel($edgeGatewayModel);
        $this->setResponse(new EdgeGatewayResponseModel());

        $this->getResponse()->setSerialNumber($edgeGatewayModel ? $edgeGatewayModel->getSerialNumber() : null);
        $this->communicationLogManager->setRequest($this->getRequest());

        // check if DT is configured correctly for this communication procedure
        if (!$this->isCommunicationProcedureRequirementsSatisfied($this->getCommunicationProcedureRequirementsRequired())) {
            // detailed logs will be filled by method above
            $this->communicationLogManager->createLogCritical('log.communicationProcedureRequirementsNotSatisfied');
            // RESPONSE
            $this->getResponse()->setError('Device Type configuration error.');

            return $this->getResponse();
        }

        return null;
    }

    public function processEdgeGatewayRequest(DeviceType $deviceType, Request $request, EdgeGatewayModel $edgeGatewayModel): ResponseModel
    {
        $initilizeResponse = $this->initilizeEdgeGatewayEndpoint($deviceType, $request, $edgeGatewayModel);
        if ($initilizeResponse) {
            return $initilizeResponse;
        }

        $this->setDevice($this->findDeviceByIdentifier($this->getEdgeGatewayModel()->getSerialNumber()));

        $this->communicationLogManager->createLogInfo('log.incomingRequest');

        if (!$this->getDevice()) {
            $this->processMissingEdgeGateway();
        } else {
            $this->communicationLogManager->setDevice($this->getDevice());
            $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
            $this->communicationLogManager->createLogDebug('log.deviceFound');
        }

        if (!$this->getDevice()) {
            // if processMissingEdgeGateway didn't create device finish communication procedure
            return $this->getResponse();
        }

        if ($this->processDeviceTypeMismatch()) {
            return $this->getResponse();
        }

        $this->updateEdgeGatewayLastDataInformation();
        $this->processReceivedCommand();

        if (!$this->getDevice()->getEnabled()) {
            $communicationLog = $this->communicationLogManager->createLogWarning('log.deviceDisabled');
            $this->getResponse()->setError($communicationLog->getMessage());

            return $this->getResponse();
        }

        $incrementConnections = true;

        if ($this->processFirmware(Feature::PRIMARY, $this->getEdgeGatewayModel()->getFirmwareVersion())) {
            $this->getDevice()->setReinstallFirmware1(true);
        }

        // Firmware operations
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

            $reinstallConfig = $this->processReinstallConfig(Feature::PRIMARY);

            if (!$reinstallConfig) {
                $requestConfig = $this->processRequestConfigData();
            }
        }
        $this->communicationLogManager->createLogDebug('log.requestProcessed', [], $this->getDeviceModelResponse($this->getResponse())->__toString());

        $this->entityManager->flush();

        if ($incrementConnections) {
            $this->incrementDeviceConnections();
            $this->entityManager->flush();
        }

        $this->getResponse()->setSerialNumber($this->getDevice()->getSerialNumber());

        return $this->getResponse();
    }

    protected function processFirmware(Feature $feature, string $receivedFirmwareVersion, bool $createLogs = true): bool
    {
        if ($this->getDevice()->getReinstallFirmware1()) {
            $this->communicationLogManager->createLogDebug('log.deviceReinstallFirmware1AlreadySet');

            return false;
        }
        if ($this->getDevice()->getLastCommandCritical()) {
            $this->communicationLogManager->createLogWarning('log.deviceReinstallFirmwareLastCommandCriticalTrue');

            return false;
        }

        if (!parent::processFirmware($feature, $receivedFirmwareVersion, $createLogs)) {
            return false;
        }
        if ($this->getDevice()->getCommandRetryCount() > $this->getDeviceType()->getDeviceCommandMaxRetries()) {
            $this->communicationLogManager->createLogWarning('log.deviceReinstallFirmware1CommandErrorMaxRetriesExceeded', [
                'commandRetryCount' => $this->getDevice()->getCommandRetryCount(),
            ]);

            return false;
        }

        return true;
    }

    protected function handleRequestConfigData(): void
    {
        $commandName = EdgeGatewayCommandName::GETCONFIG;
        $this->getResponse()->setCommandName($commandName->value);

        $command = $this->createCommand($commandName->value);
        $this->getResponse()->setCommandTransactionId($command->getCommandTransactionId());

        $this->getDevice()->setRequestConfigData(false);

        $this->entityManager->persist($this->getDevice());
        $this->entityManager->flush();
    }

    protected function handleReinstallFirmware(Feature $feature, Firmware $firmware): void
    {
        $commandName = EdgeGatewayCommandName::UPDATEFIRMWARE;
        $this->getResponse()->setCommandName($commandName->value);
        $this->getResponse()->setFirmwareUrl($this->getFirmwareUrl($feature, $firmware));

        $command = $this->createCommand($commandName->value);
        $this->getResponse()->setCommandTransactionId($command->getCommandTransactionId());

        $this->getDevice()->setReinstallFirmware1(false);

        $this->entityManager->persist($this->getDevice());
        $this->entityManager->flush();
    }

    protected function handleReinstallConfig(Feature $feature, ConfigDevice $generatedConfig): void
    {
        $commandName = EdgeGatewayCommandName::UPDATECONFIG;
        $this->getResponse()->setCommandName($commandName->value);

        $deviceType = $this->getDeviceType();

        switch ($deviceType->getFormatConfig1()) {
            case ConfigFormat::PLAIN:
                $this->getResponse()->setConfig($generatedConfig->getConfigGenerated());
                break;
            case ConfigFormat::JSON:
                // Read more about SerializableJson use case in App\Serializer\Normalizer\SerializableJsonNormalizer.
                $this->getResponse()->setConfig(new SerializableJson($generatedConfig->getConfigGenerated()));
                break;
            default:
                throw new \Exception('Unsupported config format: \"'.$deviceType->getFormatConfig1()?->value.'\"');
        }

        $command = $this->createCommand($commandName->value);
        $this->getResponse()->setCommandTransactionId($command->getCommandTransactionId());

        $this->getDevice()->setReinstallConfig1(false);
        $this->entityManager->persist($this->getDevice());
        $this->entityManager->flush();
    }

    protected function processReceivedCommand()
    {
        switch ($this->getEdgeGatewayModel()->getCommandName()) {
            case EdgeGatewayCommandName::UPDATEFIRMWARE:
                $this->processCommandNameUpdateFirmware();
                break;
            case EdgeGatewayCommandName::UPDATECONFIG:
                $this->processCommandNameUpdateConfig();
                break;
            case EdgeGatewayCommandName::GETCONFIG:
                $this->processCommandNameGetConfig();
                break;
        }
    }

    protected function processPendingCommands(): void
    {
        $queryBuilder = $this->getRepository(DeviceCommand::class)->createQueryBuilder('dc');
        $queryBuilder->andWhere('dc.device = :device');
        $queryBuilder->andWhere('dc.commandStatus = :commandStatus');
        $queryBuilder->andWhere('dc.commandTransactionId != :commandTransactionId');
        $queryBuilder->setParameter('device', $this->getDevice());
        $queryBuilder->setParameter('commandStatus', DeviceCommandStatus::PENDING);
        $queryBuilder->setParameter('commandTransactionId', $this->getEdgeGatewayModel()->getCommandTransactionId());

        $pendingCommands = $queryBuilder->getQuery()->getResult();

        foreach ($pendingCommands as $pendingCommand) {
            $pendingCommand->setCommandStatus(DeviceCommandStatus::EXPIRED);
            $this->getDevice()->setCommandRetryCount($this->getDevice()->getCommandRetryCount() + 1);

            $this->entityManager->persist($this->getDevice());
            $this->entityManager->persist($pendingCommand);
            $this->entityManager->flush();

            $this->communicationLogManager->createLogError('log.deviceCommandPendingExists', [
                'commandTransactionId' => $pendingCommand->getCommandTransactionId(),
                'commandRetryCount' => $this->getDevice()->getCommandRetryCount(),
            ]);
        }
    }

    protected function findCommand(): ?DeviceCommand
    {
        $commandTransactionId = $this->getEdgeGatewayModel()->getCommandTransactionId();

        return $this->getRepository(DeviceCommand::class)->findOneBy([
            'device' => $this->getDevice(),
            'commandTransactionId' => $commandTransactionId,
            'commandStatus' => DeviceCommandStatus::PENDING,
            'commandName' => $this->getEdgeGatewayModel()->getCommandName(),
        ]);
    }

    protected function updateCommand(DeviceCommand $command): void
    {
        $device = $command->getDevice();

        $command->setCommandStatusErrorCategory($this->getEdgeGatewayModel()->getCommandStatusErrorCategory());
        $command->setCommandStatusErrorPid($this->getEdgeGatewayModel()->getCommandStatusErrorPid());
        $command->setCommandStatusErrorMessage($this->getEdgeGatewayModel()->getCommandStatusErrorMessage());

        switch ($this->getEdgeGatewayModel()->getCommandStatus()) {
            case EdgeGatewayCommandStatus::SUCCESS:
                $command->setCommandStatus(DeviceCommandStatus::SUCCESS);
                $device->setCommandRetryCount(0);
                $device->setLastCommandCritical(false);

                $this->communicationLogManager->createLogInfo('log.deviceCommandUpdateSuccess');

                break;
            case EdgeGatewayCommandStatus::ERROR:
                $command->setCommandStatus(DeviceCommandStatus::ERROR);
                $device->setCommandRetryCount($device->getCommandRetryCount() + 1);
                $device->setLastCommandCritical(false);

                $this->communicationLogManager->createLogError('log.deviceCommandUpdateError', [
                    'errorCategory' => $command->getCommandStatusErrorCategory(),
                ]);

                break;
            case EdgeGatewayCommandStatus::CRITICAL:
                $command->setCommandStatus(DeviceCommandStatus::CRITICAL);

                $this->setLastCommandCriticalTrue($device);

                $this->communicationLogManager->createLogCritical('log.deviceCommandUpdateCritical', [
                    'errorCategory' => $command->getCommandStatusErrorCategory(),
                ]);

                break;
        }

        $this->entityManager->persist($device);
        $this->entityManager->persist($command);
        $this->entityManager->flush();
    }

    protected function processCommand(): ?DeviceCommand
    {
        $this->processPendingCommands();

        $command = $this->findCommand();

        if (!$command) {
            $this->communicationLogManager->createLogError('log.deviceCommandMissing');

            return null;
        }

        $this->updateCommand($command);

        return $command;
    }

    protected function processCommandNameUpdateFirmware(): void
    {
        $command = $this->processCommand();

        if (!$command) {
            return;
        }

        if (DeviceCommandStatus::ERROR === $command->getCommandStatus()) {
            if ($this->getDevice()->getCommandRetryCount() <= $this->getDeviceType()->getDeviceCommandMaxRetries()) {
                $this->communicationLogManager->createLogDebug('log.edgeGateway.processCommandNameUpdateFirmware.commandErrorRetry', [
                     'commandRetryCount' => $this->getDevice()->getCommandRetryCount(),
                ]);

                $this->getDevice()->setReinstallFirmware1(true);

                $this->entityManager->persist($this->getDevice());
                $this->entityManager->flush();
            } else {
                $this->communicationLogManager->createLogCritical('log.edgeGateway.processCommandNameUpdateFirmware.commandErrorRetriesExceeded', [
                    'commandRetryCount' => $this->getDevice()->getCommandRetryCount(),
                ]);

                $this->setLastCommandCriticalTrue($this->getDevice());

                $this->entityManager->persist($this->getDevice());
                $this->entityManager->flush();
            }
        }
    }

    protected function processCommandNameUpdateConfig(): void
    {
        $command = $this->processCommand($this->getEdgeGatewayModel(), $this->getDevice());

        if (!$command) {
            return;
        }

        if (DeviceCommandStatus::ERROR === $command->getCommandStatus()) {
            if ($this->getDevice()->getCommandRetryCount() <= $this->getDeviceType()->getDeviceCommandMaxRetries()) {
                $this->communicationLogManager->createLogDebug('log.edgeGateway.processCommandNameUpdateConfig.commandErrorRetry', [
                    'commandRetryCount' => $this->getDevice()->getCommandRetryCount(),
               ]);

                $this->getDevice()->setReinstallConfig1(true);

                $this->entityManager->persist($this->getDevice());
                $this->entityManager->flush();
            } else {
                $this->communicationLogManager->createLogCritical('log.edgeGateway.processCommandNameUpdateConfig.commandErrorRetriesExceeded', [
                    'commandRetryCount' => $this->getDevice()->getCommandRetryCount(),
                ]);

                $this->setLastCommandCriticalTrue($this->getDevice());

                $this->entityManager->persist($this->getDevice());
                $this->entityManager->flush();
            }
        }
    }

    protected function processCommandNameGetConfig(): void
    {
        $command = $this->processCommand($this->getEdgeGatewayModel(), $this->getDevice());

        if (!$command) {
            return;
        }

        $this->processReceivedConfigLog(json_encode($this->getEdgeGatewayModel()->getConfig()));

        if (DeviceCommandStatus::ERROR === $command->getCommandStatus()) {
            if ($this->getDevice()->getCommandRetryCount() <= $this->getDeviceType()->getDeviceCommandMaxRetries()) {
                $this->communicationLogManager->createLogDebug('log.edgeGateway.processCommandNameGetConfig.commandErrorRetry', [
                    'commandRetryCount' => $this->getDevice()->getCommandRetryCount(),
               ]);

                $this->getDevice()->setRequestConfigData(true);

                $this->entityManager->persist($this->getDevice());
                $this->entityManager->flush();
            } else {
                $this->communicationLogManager->createLogCritical('log.edgeGateway.processCommandNameGetConfig.commandErrorRetriesExceeded', [
                    'commandRetryCount' => $this->getDevice()->getCommandRetryCount(),
                ]);

                $this->setLastCommandCriticalTrue($this->getDevice());

                $this->entityManager->persist($this->getDevice());
                $this->entityManager->flush();
            }
        }
    }

    protected function processMissingEdgeGateway()
    {
        $this->communicationLogManager->createLogInfo('log.deviceNotFound', ['identifier' => $this->getEdgeGatewayModel()->getSerialNumber()]);

        $this->createEdgeGateway();
    }

    protected function createEdgeGateway()
    {
        $this->setDevice(new Device());
        $this->getDevice()->setDeviceType($this->getDeviceType());
        $this->getDevice()->setVirtualSubnetCidr($this->getDeviceType()->getVirtualSubnetCidr());
        $this->getDevice()->setMasqueradeType($this->getDeviceType()->getMasqueradeType());
        $this->getDevice()->setName($this->getEdgeGatewayModel()->getSerialNumber());
        $this->getDevice()->setSerialNumber($this->getEdgeGatewayModel()->getSerialNumber());
        $this->getDevice()->setUuid($this->getDeviceTypeUniqueUuid());
        $this->getDevice()->setHashIdentifier($this->getDeviceUniqueHashIdentifier());
        $this->getDevice()->setIdentifier($this->generateIdentifier($this->getDevice()));

        $this->updateEdgeGatewayLastDataInformation();

        $this->communicationLogManager->setDevice($this->getDevice());
        $this->communicationLogManager->updateCommunicationLogsWithoutDevice();

        $this->communicationLogManager->createLogInfo('log.deviceCreate');

        $this->entityManager->persist($this->getDevice());

        $this->incrementDeviceConnections();

        $this->entityManager->flush();
    }

    // this function is used in controller directly
    protected function handleErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form, array $messages, string $message): Response|ResponseModel
    {
        $response = new EdgeGatewayResponseModel();
        $response->setError($message);

        return $response;
    }

    protected function updateEdgeGatewayLastDataInformation(): void
    {
        $this->fillCommunicationData($this->getDevice());
        $this->fillGsmData($this->getDevice());
        $this->fillVersionFirmware1($this->getDevice());
    }

    public function fillCommunicationData(CommunicationEntityInterface $entity): CommunicationEntityInterface
    {
        if ($this->getEdgeGatewayModel()) {
            if ($this->getEdgeGatewayModel()->getRegistrationId()) {
                $entity->setRegistrationId($this->getEdgeGatewayModel()->getRegistrationId());
            }

            if ($this->getEdgeGatewayModel()->getEndorsementKey()) {
                $entity->setEndorsementKey($this->getEdgeGatewayModel()->getEndorsementKey());
            }

            if ($this->getEdgeGatewayModel()->getHardwareVersion()) {
                $entity->setHardwareVersion($this->getEdgeGatewayModel()->getHardwareVersion());
            }

            if ($this->getEdgeGatewayModel()->getSerialNumber()) {
                $entity->setSerialNumber($this->getEdgeGatewayModel()->getSerialNumber());
            }
        }

        return parent::fillCommunicationData($entity);
    }

    public function fillGsmData(GsmEntityInterface $entity): GsmEntityInterface
    {
        if ($this->getEdgeGatewayModel()) {
            if ($this->getEdgeGatewayModel()->getImsi()) {
                $entity->setImsi($this->getEdgeGatewayModel()->getImsi());
            }
            if ($this->getEdgeGatewayModel()->getImei()) {
                $entity->setImsi($this->getEdgeGatewayModel()->getImei());
            }
            if ($this->getEdgeGatewayModel()->getNetworkGeneration()) {
                $entity->setNetworkGeneration($this->getEdgeGatewayModel()->getNetworkGeneration());
            }
        }

        return $entity;
    }

    public function fillVersionFirmware1(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        if ($this->getEdgeGatewayModel()) {
            if ($this->getEdgeGatewayModel()->getFirmwareVersion()) {
                $entity->setFirmwareVersion1($this->getEdgeGatewayModel()->getFirmwareVersion());
            }
        }

        return $entity;
    }

    protected function getCustomDeviceVariables(bool $createLogs = true): array
    {
        return [
            VariableInterface::VARIABLE_NAME_REGISTRATIONID => $this->getDevice()?->getRegistrationId(),
            VariableInterface::VARIABLE_NAME_ENDORSEMENTKEY => $this->getDevice()?->getEndorsementKey(),
            VariableInterface::VARIABLE_NAME_HARDWAREVERSION => $this->getDevice()?->getHardwareVersion(),
            VariableInterface::VARIABLE_NAME_FIRMWAREVERSION => $this->getDevice()?->getFirmwareVersion1(),
        ];
    }

    protected function setLastCommandCriticalTrue(): void
    {
        $this->getDevice()->setCommandRetryCount(0);
        $this->getDevice()->setLastCommandCritical(true);

        // Stop any future commands
        $this->getDevice()->setReinstallFirmware1(false);
        $this->getDevice()->setReinstallConfig1(false);
        $this->getDevice()->setRequestConfigData(false);
    }
}
