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

use App\DeviceCommunication\Trait\RouterCommunicationInterface;
use App\Entity\CommunicationLog;
use App\Entity\Config;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Entity\Traits\CommunicationEntityInterface;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Entity\Traits\GsmEntityInterface;
use App\Enum\CertificateCategory;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\Feature;
use App\Enum\FieldRequirement;
use App\Enum\RouterIdentifier;
use App\Form\DeviceCommunication\RouterType;
use App\Helper\UptimeConverter;
use App\Model\ConfigDevice;
use App\Model\FieldRequirementsModel;
use App\Model\ResponseModel;
use App\Model\RouterModel;
use App\Model\VariableInterface;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\TranslatorTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\Service\Attribute\Required;

class RouterOneConfigCommunication extends AbstractDeviceCommunication implements RouterCommunicationInterface
{
    use ConfigurationManagerTrait;
    use TranslatorTrait;

    public const RESPONSE_FIRMWARE_HEADER = 'configuration/firmwareupdate';
    public const REQUEST_CONTENTTYPE_DIAGNOSEDATA = 'dls/diagnosedata';
    public const CONFIG_TYPE_STARTUP = 'configuration/Startup-config';
    public const CONFIG_TYPE_NOCHANGE = 'configuration/NoChange';
    public const CONFIG_TYPE_REQUESTDIAGNOSEDATA = 'diagnose/senddiagnose';

    /**
     * @var ?RouterModel
     */
    protected $routerModel;

    public function getRouterModel(): ?RouterModel
    {
        return $this->routerModel;
    }

    public function setRouterModel(?RouterModel $routerModel)
    {
        $this->routerModel = $routerModel;
    }

    /**
     * @var ?array
     */
    protected $uppercaseFirmwareVersions;

    #[Required]
    public function setUppercaseFirmwareVersions(null|string $uppercaseFirmwareVersions = null)
    {
        $this->uppercaseFirmwareVersions = $uppercaseFirmwareVersions ? explode(' ', $uppercaseFirmwareVersions) : null;
    }

    public function getRoutes(DeviceType $deviceType): RouteCollection
    {
        $routeCollection = new RouteCollection();

        $path = $deviceType->getValidRoutePrefix().'/config';
        $defaults = [
                '_controller' => 'App\Controller\DeviceCommunication\RouterOneConfigController::configAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = 'routerOneConfigConfig'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        return $routeCollection;
    }

    public function getCommunicationProcedureRequirementsOptional(): array
    {
        $requirements = [
            CommunicationProcedureRequirement::HAS_CERTIFICATES,
            CommunicationProcedureRequirement::HAS_VARIABLES,
            CommunicationProcedureRequirement::HAS_REQUEST_DIAGNOSE,
            CommunicationProcedureRequirement::HAS_VPN,
            CommunicationProcedureRequirement::HAS_ENDPOINT_DEVICES,
        ];

        return $requirements;
    }

    public function getCommunicationProcedureRequirementsRequired(): array
    {
        $requirements = [
            CommunicationProcedureRequirement::HAS_GSM,
            CommunicationProcedureRequirement::HAS_FIRMWARE1,
            CommunicationProcedureRequirement::HAS_CONFIG1,
            CommunicationProcedureRequirement::HAS_TEMPLATES,
        ];

        return $requirements;
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

        if ($this->configurationManager->isImsiRouterIdentifier()) {
            $fieldRequirements->setFieldImsi(FieldRequirement::REQUIRED);
        } else {
            $fieldRequirements->setFieldImsi(FieldRequirement::OPTIONAL);
        }

        $fieldRequirements->setFieldModel(FieldRequirement::OPTIONAL);

        return $fieldRequirements;
    }

    public function generateIdentifier(Device $device): string
    {
        if ($this->configurationManager->isSerialRouterIdentifier()) {
            if ($device->getSerialNumber()) {
                return $device->getSerialNumber();
            }
        }

        if ($this->configurationManager->isImsiRouterIdentifier()) {
            if ($device->getImsi()) {
                return $device->getImsi();
            }

            if ($device->getSerialNumber()) {
                return $device->getSerialNumber();
            }
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
                VariableInterface::VARIABLE_NAME_IMSI_UPPERCASE,
                VariableInterface::VARIABLE_NAME_SOURCEIP,
                VariableInterface::VARIABLE_NAME_XFORWARDEDFORIP,
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
                    VariableInterface::VARIABLE_NAME_CERTIFICATE,
                    VariableInterface::VARIABLE_NAME_CERTIFICATE_PLAIN,
                    VariableInterface::VARIABLE_NAME_CERTIFICATE_CHECKSUM,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY_PLAIN,
                    VariableInterface::VARIABLE_NAME_PRIVATE_KEY_CHECKSUM,
                    VariableInterface::VARIABLE_NAME_CA,
                    VariableInterface::VARIABLE_NAME_CA_PLAIN,
                    VariableInterface::VARIABLE_NAME_CA_CHECKSUM,
                    VariableInterface::VARIABLE_NAME_ROOT_CA,
                    VariableInterface::VARIABLE_NAME_ROOT_CA_PLAIN,
                    VariableInterface::VARIABLE_NAME_ROOT_CA_CHECKSUM,
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
        $routerModel = $this->executePostAuthenticatorForm($request, RouterType::class, ['validation_groups' => 'authentication'], true, true);
        if (!$routerModel) {
            return null;
        }

        // Checking if sn was provided as validation groups are not used
        if (!$routerModel->getSerial() && !$routerModel->getIMSI()) {
            return null;
        }

        return $this->findRouterByModel($routerModel);
    }

    public function process(DeviceType $deviceType, Request $request, RouterModel $routerModel): Response
    {
        $this->setDeviceType($deviceType);
        $this->setRequest($request);
        $this->setRouterModel($routerModel);
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

        $this->setDevice($this->findRouter());

        $this->communicationLogManager->createLogInfo('log.incomingRequest');

        $incrementConnections = false;

        if (!$this->getDevice()) {
            $this->processMissingRouter();
        } else {
            $this->communicationLogManager->setDevice($this->getDevice());
            $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
            if ($this->processDeviceTypeMismatch()) {
                return $this->getResponse();
            }
            $this->processRouter();
            $incrementConnections = true;
        }
        $this->communicationLogManager->createLogDebug('log.requestProcessed', [], $this->getResponse()->__toString());

        $this->entityManager->flush();

        if ($incrementConnections) {
            $this->incrementDeviceConnections();
            $this->entityManager->flush();
        }

        $this->communicationLogManager->clearRequest();

        return $this->getResponse();
    }

    /**
     * Processing missing Router. Possible when:
     * - Identifier is IMSI = New IMSI was sent
     * - Identifier is Serial = New Serial was sent.
     *
     * @return Response
     */
    protected function processMissingRouter()
    {
        if (RouterIdentifier::IMSI == $this->configurationManager->getRouterIdentifier()) {
            if ($this->getRouterModel()->getIMSI()) {
                $this->communicationLogManager->createLogInfo('log.routerImsiDoesntExist', ['imsi' => $this->getRouterModel()->getIMSI()]);
                // RESPONSE
                $this->getResponse()->setContent('Router with IMSI = '.$this->getRouterModel()->getIMSI().' does not exist. Creating new router.');
            } else {
                $this->communicationLogManager->createLogInfo('log.routerImsiEmpty', ['serial' => $this->getRouterModel()->getSerial()]);
                // RESPONSE
                $this->getResponse()->setContent('IMSI is empty. Router with Serial = '.$this->getRouterModel()->getSerial().' does not exist. Creating new router.');
            }
        } else {
            $this->communicationLogManager->createLogInfo('log.routerSerialDoesntExist', ['serial' => $this->getRouterModel()->getSerial()]);
            // RESPONSE
            $this->getResponse()->setContent('Router with Serial = '.$this->getRouterModel()->getSerial().' does not exist. Creating new router.');
        }

        $this->invalidateRouter();
        $this->createRouter();
    }

    protected function handleDeviceTypeMismatch(CommunicationLog $communicationLog)
    {
        // RESPONSE
        $this->getResponse()->setContent('Router found, but expected type does not match. Please use appropriate endpoint for this router type.');
    }

    /**
     * Processing existing Router.
     */
    protected function processRouter()
    {
        $this->updateLastDataInformation();

        if (!$this->getDevice()->getEnabled()) {
            $this->processDisabledRouter();
        } else {
            $this->processRouterSerialNumber();
            $this->processRouterImei();
            $this->processRouterImsi();

            if ($this->processFirmware(Feature::PRIMARY, $this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware()))) {
                $this->getDevice()->setReinstallFirmware1(true);
            }

            if (!$this->processRequestDiagnoseData()) {
                // Firmware operations
                $reinstallingFirmware = $this->processReinstallFirmware(Feature::PRIMARY);

                // If not reinstalling firmware, try to send StartupConfig
                if (!$reinstallingFirmware) {
                    // Check if config will be sent if certificate or deviceSecret will renew or generate
                    $shouldReinstallConfig = $this->getShouldReinstallConfig(
                        feature: Feature::PRIMARY,
                        currentConfig: $this->getRouterModel()->getConfig(),
                        sendTheSameConfig: false,
                        createLogs: false,
                        expectedReinstallConfigFlag: true
                    );

                    if ($shouldReinstallConfig) {
                        if ($this->processAutoRenewCertificates()) {
                            $this->getDevice()->setReinstallConfig1(true);
                        }

                        if ($this->processAutoGenerationOrRenewDeviceSecrets()) {
                            $this->getDevice()->setReinstallConfig1(true);
                        }
                    }

                    $reinstallConfig = $this->processReinstallConfig(Feature::PRIMARY, $this->getRouterModel()->getConfig(), false);

                    if (!$reinstallConfig) {
                        $this->communicationLogManager->createLogInfo('log.deviceNoConfigWillBeSent');
                        $this->handleNoChangeResponse();
                    }
                }
            }
        }

        if (!$this->processReceivedDiagnoseData(self::REQUEST_CONTENTTYPE_DIAGNOSEDATA, $this->getRouterModel()->getConfig())) {
            // if request is not diagnose data, try to save config
            $this->processReceivedConfigLog($this->getRouterModel()->getConfig());
        }

        $this->entityManager->persist($this->getDevice());
    }

    /**
     * Processing Router Serial Number. Processed when:
     * - Router was previously invalidated (Serial = NULL)
     * - Serial has changed (IMSI was moved between Routers)
     * (Can occur only when Identifier is IMSI).
     */
    protected function processRouterSerialNumber()
    {
        if (!$this->getDevice()->getSerialNumber() || ($this->getDevice()->getSerialNumber() != $this->getRouterModel()->getSerial())) {
            // Invalidate Router - Find by Serial and invalidate IMSI
            $this->invalidateRouter();

            if ($this->getDevice()->getSerialNumber() != $this->getRouterModel()->getSerial()) {
                $this->communicationLogManager->createLogInfo('log.routerSerialChanged', ['serial' => $this->getRouterModel()->getSerial()]);
            }

            $this->getDevice()->setSerialNumber($this->getRouterModel()->getSerial());

            if ($this->configurationManager->isSerialRouterIdentifier()) {
                $this->getDevice()->setIdentifier($this->getRouterModel()->getSerial());
                $this->getDevice()->setName($this->getRouterModel()->getSerial());
            }

            // Force reinstalling StartupConfig
            $this->getDevice()->setReinstallConfig1(true);
        }
    }

    /**
     * Processing Router IMEI. Processed when:
     * - IMEI is not present
     * - IMEI has changed (IMSI was moved between Routers).
     */
    protected function processRouterImei()
    {
        if (!$this->getDevice()->getImei() || ($this->getDevice()->getImei() != $this->getRouterModel()->getIMEI())) {
            if ($this->getDevice()->getImei() != $this->getRouterModel()->getIMEI()) {
                $this->communicationLogManager->createLogInfo('log.routerImeiChanged', ['imei' => $this->getRouterModel()->getIMEI()]);
            }

            $this->getDevice()->setImei($this->getRouterModel()->getIMEI());
        }
    }

    /**
     * Processing Router IMSI. Processed when:
     * - Router was previously invalidated (IMSI = NULL)
     * - IMSI has changed (IMSI was moved between Routers)
     * (Can occur only when Identifier is Serial).
     */
    protected function processRouterImsi()
    {
        if (!$this->getDevice()->getImsi() || ($this->getDevice()->getImsi() != $this->getRouterModel()->getIMSI())) {
            // Invalidate Router - Find by IMSI and invalidate Serial
            $this->invalidateRouter($this->getRouterModel(), $this->getDevice());

            if ($this->getDevice()->getImsi() != $this->getRouterModel()->getIMSI()) {
                $this->communicationLogManager->createLogInfo('log.routerImsiChanged', ['imsi' => $this->getRouterModel()->getIMSI()]);
            }

            $previousImsi = $this->getDevice()->getImsi();
            $this->getDevice()->setImsi($this->getRouterModel()->getIMSI());

            if ($this->configurationManager->isImsiRouterIdentifier()) {
                if ($this->getRouterModel()->getIMSI()) {
                    $this->getDevice()->setIdentifier($this->getRouterModel()->getIMSI());
                    $this->getDevice()->setName($this->getRouterModel()->getIMSI());
                }
            }
        }
    }

    /**
     * Processing Router Firmware.
     */
    protected function processRouterFirmware()
    {
        if (!$this->getDeviceTemplate()) {
            $this->communicationLogManager->createLogInfo('log.deviceFirmware1NoTemplate');
        } elseif (!$this->getDeviceTemplate()->getFirmware1()) {
            $this->communicationLogManager->createLogInfo('log.deviceFirmware1NoFirmware');
        } elseif ($this->getDeviceTemplate()->getFirmware1()->getVersion() != $this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware())) {
            $this->communicationLogManager->createLogInfo('log.deviceFirmware1NeedsUpdate', [
                'currentVersion' => $this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware()),
                'requiredVersion' => $this->getDeviceTemplate()->getFirmware1()->getVersion(),
            ]);

            $this->getDevice()->setReinstallFirmware1(true);
        } else {
            $this->communicationLogManager->createLogInfo('log.deviceFirmware1UpToDate');
        }
    }

    /**
     * Processing disabled Router.
     */
    protected function processDisabledRouter()
    {
        if (!$this->getDeviceTemplate()) {
            $this->communicationLogManager->createLogInfo('log.deviceDisabledNoTemplate');
            // RESPONSE
            $this->getResponse()->setContent('Router is disabled and does not have Template.');
        } elseif (!$this->getDeviceTemplate()->getConfig1()) {
            $this->communicationLogManager->createLogInfo('log.deviceDisabledNoConfig1');
            // RESPONSE
            $this->getResponse()->setContent('Router is disabled and its template has no StartupConfig.');
        } else {
            $this->communicationLogManager->createLogInfo('log.deviceDisabled');
            // RESPONSE
            $this->getResponse()->setContent('Router is disabled.');
        }
    }

    /**
     * Check if RSRP is Valid.
     */
    protected function isRsrpValid(?int $requiredMinRsrp): bool
    {
        // the less -> the better. So.
        // $this->minRSRP = -116
        // we got -90 (or 90) from $this->getRouterModel(); not sure in what format router sends it - but it does not matter(?)
        // -> abs(-116) = 116
        // -> abs(-90 || 90) = 90
        // 116 <= 90 -> TRUE
        return abs($requiredMinRsrp ?: 0) >= abs($this->getRouterModel()->getRSRP() ?: 0);
    }

    /**
     * Invalidate Router = set proper identifier to NULL.
     * - Identifier is IMSI = Find by Serial and invalidate IMSI
     * - Identifier is Serial = Find by IMSI and invalidate Serial.
     */
    protected function invalidateRouter()
    {
        $invalidRouter = null;

        if ($this->configurationManager->isImsiRouterIdentifier()) {
            // Identifier is IMSI = Find by Serial
            $invalidRouter = $this->findRouterForInvalidation($this->getRouterModel(), RouterIdentifier::SERIAL);
        }

        if ($this->configurationManager->isSerialRouterIdentifier()) {
            // Identifier is Serial = Find by IMSI
            $invalidRouter = $this->findRouterForInvalidation($this->getRouterModel(), RouterIdentifier::IMSI);
        }

        if (!$invalidRouter) {
            return;
        }

        if ($this->getDevice() && $invalidRouter === $this->getDevice()) {
            return;
        }

        $message = null;
        if ($this->configurationManager->isImsiRouterIdentifier()) {
            if (!$invalidRouter->getImsi()) {
                $identifier = 'INVALID '.$invalidRouter->getSerialNumber();
                $invalidRouter->setIdentifier($identifier);
                $invalidRouter->setName($identifier);

                $this->communicationLogManager->createLogCritical('log.routerInvalidatedLostIdentifier', [], null, $invalidRouter);
            }
            $invalidRouter->setSerialNumber(null);
            $message = 'log.routerInvalidatedImsiChanged';
        }

        if ($this->configurationManager->isSerialRouterIdentifier()) {
            $invalidRouter->setImsi(null);
            $message = 'log.routerInvalidatedSerialChanged';
        }

        if ($message) {
            $this->communicationLogManager->createLogWarning($message, [], null, $invalidRouter);

            $this->entityManager->persist($invalidRouter);
            $this->entityManager->flush();
        }
    }

    protected function createRouter()
    {
        $this->setDevice(new Device());
        $this->getDevice()->setSerialNumber($this->getRouterModel()->getSerial());
        $this->getDevice()->setImsi($this->getRouterModel()->getIMSI());
        $this->getDevice()->setImei($this->getRouterModel()->getIMEI());
        $this->getDevice()->setDeviceType($this->getDeviceType());
        $this->getDevice()->setVirtualSubnetCidr($this->getDeviceType()->getVirtualSubnetCidr());
        $this->getDevice()->setMasqueradeType($this->getDeviceType()->getMasqueradeType());
        $this->getDevice()->setUuid($this->getDeviceTypeUniqueUuid());
        $this->getDevice()->setHashIdentifier($this->getDeviceUniqueHashIdentifier());

        $identifier = $this->generateIdentifier($this->getDevice());
        $this->getDevice()->setIdentifier($identifier);
        $this->getDevice()->setName($identifier);

        $this->updateLastDataInformation();

        $this->communicationLogManager->setDevice($this->getDevice());
        $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
        $this->communicationLogManager->createLogInfo('log.deviceCreate');

        $this->entityManager->persist($this->getDevice());

        $this->incrementDeviceConnections();
    }

    // this function is used in controller directly
    protected function handleErrorResponse(DeviceType $deviceType, Request $request, FormInterface $form, array $messages, string $message): Response|ResponseModel
    {
        // Empty response by design
        $response = new Response();
        $this->communicationLogManager->createLogDebug('log.deviceInvalidRequestSendingEmpty', [], $response->__toString());

        $this->entityManager->flush();

        return $response;
    }

    protected function findRouter(): ?Device
    {
        return $this->findRouterByModel($this->getRouterModel());
    }

    protected function findRouterByModel(RouterModel $routerModel): ?Device
    {
        switch ($this->configurationManager->getRouterIdentifier()) {
            case RouterIdentifier::IMSI:
                if ($routerModel->getIMSI()) {
                    $router = $this->getRepository(Device::class)->findOneBy(['imsi' => $routerModel->getIMSI(), 'deviceType' => $this->getDeviceType()]);

                    if ($router) {
                        return $router;
                    }
                }

                if ($routerModel->getSerial()) {
                    return $this->getRepository(Device::class)->findOneBy(['serialNumber' => $routerModel->getSerial(), 'deviceType' => $this->getDeviceType()]);
                }

                break;
            case RouterIdentifier::SERIAL:
                if ($routerModel->getSerial()) {
                    return $this->getRepository(Device::class)->findOneBy(['serialNumber' => $routerModel->getSerial(), 'deviceType' => $this->getDeviceType()]);
                }
                break;
        }

        return null;
    }

    protected function findRouterForInvalidation(RouterModel $routerModel, RouterIdentifier $identifier): ?Device
    {
        switch ($identifier) {
            case RouterIdentifier::IMSI:
                if ($routerModel->getIMSI()) {
                    return $this->getRepository(Device::class)->findOneBy(['imsi' => $routerModel->getIMSI(), 'deviceType' => $this->getDeviceType()]);
                }
                break;
            case RouterIdentifier::SERIAL:
                if ($routerModel->getSerial()) {
                    return $this->getRepository(Device::class)->findOneBy(['serialNumber' => $routerModel->getSerial(), 'deviceType' => $this->getDeviceType()]);
                }
                break;
        }

        return null;
    }

    protected function normalizeFirmwareVersion(string $firmwareVersion): string
    {
        if (str_starts_with($firmwareVersion, 'v')) {
            return substr($firmwareVersion, 1);
        }
        if (str_starts_with($firmwareVersion, 'V')) {
            return substr($firmwareVersion, 1);
        }

        return $firmwareVersion;
    }

    protected function normalizeContentType(string $contentType, string $firmwareVersion): string
    {
        if ($this->isFirmwareVersionInUppercase($firmwareVersion)) {
            return $contentType;
        }

        return strtolower($contentType);
    }

    protected function isFirmwareVersionInUppercase(string $firmwareVersion): bool
    {
        if (!$this->uppercaseFirmwareVersions) {
            return false;
        }

        foreach ($this->uppercaseFirmwareVersions as $version) {
            $pattern = str_replace(['(', ')', '.', '?', '*'], ["\(", "\)", "\.", '.', '.+'], $version);
            if (1 == preg_match('/^'.$pattern.'$/', $firmwareVersion)) {
                return true;
            }
        }

        return false;
    }

    protected function handleReinstallConfig(Feature $feature, ConfigDevice $generatedConfigDevice): void
    {
        $firmwareVersion = $this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware());

        if (Feature::PRIMARY == $feature) {
            $contentType = self::CONFIG_TYPE_STARTUP;
            $this->getDevice()->setReinstallConfig1(false);
        }

        $this->getResponse()->headers->set('Content-Type', $this->normalizeContentType($contentType, $firmwareVersion));
        $this->getResponse()->setContent($generatedConfigDevice->getConfigGenerated());
    }

    protected function handleReinstallFirmware(Feature $feature, Firmware $firmware): void
    {
        $this->getResponse()->headers->set('Content-Type', $this->normalizeContentType(self::RESPONSE_FIRMWARE_HEADER, $this->normalizeFirmwareVersion($firmware->getVersion())));
        $this->getResponse()->setContent('URL="'.$this->getFirmwareUrl($feature, $firmware)."\"\r\nBooloader=Ture\r\nMD5=".$firmware->getMd5()."\r\n");
        // todo update TURE? - Awaiting customer decision

        $this->getDevice()->setReinstallFirmware1(false);
        $this->entityManager->persist($this->getDevice());
    }

    protected function handleRequestDiagnoseData(): void
    {
        $firmwareVersion = $this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware());

        $this->getResponse()->headers->set('Content-Type', $this->normalizeContentType(self::CONFIG_TYPE_REQUESTDIAGNOSEDATA, $firmwareVersion));
    }

    protected function handleNoChangeResponse(): void
    {
        $firmwareVersion = $this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware());

        $this->getResponse()->headers->set('Content-Type', $this->normalizeContentType(self::CONFIG_TYPE_NOCHANGE, $firmwareVersion));
    }

    protected function updateLastDataInformation(): void
    {
        $this->fillCommunicationData($this->getDevice(), true);
        $this->fillGsmData($this->getDevice(), true);
        $this->fillVersionFirmware1($this->getDevice());
    }

    public function fillGsmData(GsmEntityInterface $entity, bool $limited = false): GsmEntityInterface
    {
        if ($this->getRouterModel()) {
            if ($this->getDeviceType() && $this->getDeviceType()->getHasGsm()) {
                if (!$limited) {
                    // values handled differently
                    $entity->setImei($this->getRouterModel()->getIMEI());
                    $entity->setImsi($this->getRouterModel()->getIMSI());
                }
                $entity->setOperatorCode($this->getRouterModel()->getOperatorCode());
                $entity->setBand($this->getRouterModel()->getBand());
                $entity->setCellId($this->getRouterModel()->getCellID());
                $entity->setRsrp(''.$this->getRouterModel()->getRSRP());
                $entity->setRsrpValue($this->getRouterModel()->getRSRP());
                $entity->setCellularIp1($this->getRouterModel()->getCellular1IP());
                $entity->setCellularUptime1($this->getRouterModel()->getCellular1Uptime());
                $entity->setCellularUptimeSeconds1(UptimeConverter::convertToSeconds($this->getRouterModel()->getCellular1Uptime()));
                $entity->setCellularIp2($this->getRouterModel()->getCellular2IP());
                $entity->setCellularUptime2($this->getRouterModel()->getCellular2Uptime());
                $entity->setCellularUptimeSeconds2(UptimeConverter::convertToSeconds($this->getRouterModel()->getCellular2Uptime()));
            }
        }

        return $entity;
    }

    public function fillVersionFirmware1(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        if ($this->getRouterModel()) {
            if ($this->getDeviceType() && $this->getDeviceType()->getHasFirmware1()) {
                $entity->setFirmwareVersion1($this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware()));
            }
        }

        return $entity;
    }

    public function fillCommunicationData(CommunicationEntityInterface $entity, bool $limited = false): CommunicationEntityInterface
    {
        if ($this->getRouterModel()) {
            if (!$limited && $this->getRouterModel()->getSerial()) {
                // values handled differently
                $entity->setSerialNumber($this->getRouterModel()->getSerial());
            }

            if ($this->getRouterModel()->getModel()) {
                $entity->setModel($this->getRouterModel()->getModel());
            }

            $entity->setIpv6Prefix($this->getRouterModel()->getIPv6Prefix());
            $entity->setUptime($this->getRouterModel()->getRouterUptime());
            $entity->setUptimeSeconds(UptimeConverter::convertToSeconds($this->getRouterModel()->getRouterUptime()));
        }

        parent::fillCommunicationData($entity);

        return $entity;
    }
}
