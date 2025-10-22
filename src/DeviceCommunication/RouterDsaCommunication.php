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

use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\Feature;
use App\Enum\RouterIdentifier;
use App\Model\ConfigDevice;
use App\Model\RouterModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouterDsaCommunication extends RouterCommunication
{
    public const RESPONSE_AGENT_PACKAGE_HEADER = 'configuration/devicesupervisoragentpackage';
    public const RESPONSE_PYSDK_PACKAGE_HEADER = 'configuration/devicesupervisorpysdkpackage';
    public const RESPONSE_AGENT_CONFIG_HEADER = 'configuration/devicesupervisorconfig';
    public const RESPONSE_AGENT_NO_CHANGE_HEADER = 'configuration/nochange';

    public function getRoutes(DeviceType $deviceType): RouteCollection
    {
        $routeCollection = new RouteCollection();

        // route config
        $path = $deviceType->getValidRoutePrefix().'/config';
        $defaults = [
                '_controller' => 'App\Controller\DeviceCommunication\RouterDsaController::configAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = 'routerConfig'.$deviceType->getId();

        $routeCollection->add($routeName, $route);

        // route dsa
        $path = $deviceType->getValidRoutePrefix().'/device-supervisor-config';
        $defaults = [
                '_controller' => 'App\Controller\DeviceCommunication\RouterDsaController::deviceSupervisorConfigAction',
            ];
        $requirements = [];
        $options = [];
        $host = '';
        $schemes = [];
        $methods = ['POST'];
        $condition = '';
        $route = new Route($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $routeName = 'routerDeviceSupervisorConfig'.$deviceType->getId();

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
            CommunicationProcedureRequirement::HAS_ALWAYS_REINSTALL_CONFIG3,
        ];

        return $requirements;
    }

    public function getCommunicationProcedureRequirementsRequired(): array
    {
        $requirements = [
            CommunicationProcedureRequirement::HAS_GSM,
            CommunicationProcedureRequirement::HAS_CONFIG1,
            CommunicationProcedureRequirement::HAS_CONFIG2,
            CommunicationProcedureRequirement::HAS_CONFIG3,
            CommunicationProcedureRequirement::HAS_ALWAYS_REINSTALL_CONFIG2,
            CommunicationProcedureRequirement::HAS_FIRMWARE1,
            CommunicationProcedureRequirement::HAS_FIRMWARE2,
            CommunicationProcedureRequirement::HAS_FIRMWARE3,
            CommunicationProcedureRequirement::HAS_TEMPLATES,
        ];

        return $requirements;
    }

    public function processDeviceSupervisorAgent(DeviceType $deviceType, Request $request, RouterModel $routerModel): Response
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

        if (!$this->getDevice()) {
            $this->processMissingDsaRouter();
        } else {
            $this->communicationLogManager->setDevice($this->getDevice());
            $this->communicationLogManager->updateCommunicationLogsWithoutDevice();
            if ($this->processDeviceTypeMismatch()) {
                return $this->getResponse();
            }
            $this->processDsaRouter();
        }

        $this->communicationLogManager->createLogDebug('log.requestProcessed', [], $this->getResponse()->__toString());

        $this->entityManager->flush();

        $this->communicationLogManager->clearRequest();

        return $this->getResponse();
    }

    protected function processDsaRouter()
    {
        $this->updateLastDataInformation();

        if (!$this->getDevice()->getEnabled()) {
            $this->communicationLogManager->createLogInfo('log.deviceDisabled');
            // RESPONSE
            $this->getResponse()->setContent('Router is disabled.');
        } else {
            // Reinstalling DsaAgent
            if ($this->processFirmware(Feature::SECONDARY, $this->getRouterModel()->getAgentVersion())) {
                $this->getDevice()->setReinstallFirmware2(true);
            }
            if (!$this->processReinstallFirmware(Feature::SECONDARY)) {
                // Reinstalling PySdk
                if ($this->processFirmware(Feature::TERTIARY, $this->getRouterModel()->getPySdkPackageVersion())) {
                    $this->getDevice()->setReinstallFirmware3(true);
                }
                if (!$this->processReinstallFirmware(Feature::TERTIARY)) {
                    if (!$this->processReinstallConfig(Feature::TERTIARY, $this->getRouterModel()->getConfig(), false)) {
                        $this->communicationLogManager->createLogInfo('log.deviceNoConfigWillBeSent');
                        $this->getResponse()->headers->set('Content-Type', self::RESPONSE_AGENT_NO_CHANGE_HEADER);
                    }
                }
            }
        }

        $this->processReceivedConfigLog($this->getRouterModel()->getConfig(), Feature::TERTIARY);

        $this->entityManager->persist($this->getDevice());
    }

    protected function handleReinstallConfig(Feature $feature, ConfigDevice $generatedConfigDevice): void
    {
        if (Feature::PRIMARY == $feature) {
            $contentType = self::CONFIG_TYPE_STARTUP;
            $this->getDevice()->setReinstallConfig1(false);
        }
        if (Feature::SECONDARY == $feature) {
            $contentType = self::CONFIG_TYPE_RUNNING;
            $this->getDevice()->setReinstallConfig2(false);
        }

        if (Feature::TERTIARY == $feature) {
            $contentType = self::RESPONSE_AGENT_CONFIG_HEADER;
            $this->getDevice()->setReinstallConfig3(false);
        }

        if (Feature::TERTIARY !== $feature) {
            // Only normalize 1,2 config
            if ($this->getRouterModel()->getFirmware()) {
                $firmwareVersion = $this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware());
                $contentType = $this->normalizeContentType($contentType, $firmwareVersion);
            }
        }

        $this->getResponse()->headers->set('Content-Type', $contentType);
        $this->getResponse()->setContent($generatedConfigDevice->getConfigGenerated());
    }

    protected function handleReinstallFirmware(Feature $feature, Firmware $firmware): void
    {
        $bootloader = '';

        if (Feature::PRIMARY == $feature) {
            $contentType = self::RESPONSE_FIRMWARE_HEADER;
            $contentType = $this->normalizeContentType($contentType, $this->normalizeFirmwareVersion($firmware->getVersion()));
            $this->getDevice()->setReinstallFirmware1(false);
            $bootloader = "\r\nBooloader=Ture";
        }
        if (Feature::SECONDARY == $feature) {
            $contentType = self::RESPONSE_AGENT_PACKAGE_HEADER;
            $this->getDevice()->setReinstallFirmware2(false);
        }

        if (Feature::TERTIARY == $feature) {
            $contentType = self::RESPONSE_PYSDK_PACKAGE_HEADER;
            $this->getDevice()->setReinstallFirmware3(false);
        }

        $this->getResponse()->headers->set('Content-Type', $contentType);
        $this->getResponse()->setContent('URL="'.$this->getFirmwareUrl($feature, $firmware).'"'.$bootloader."\r\nMD5=".$firmware->getMd5()."\r\n");

        $this->entityManager->persist($this->getDevice());
    }

    /**
     * Processing missing Router. Possible when:
     * - Identifier is IMSI = New IMSI was sent
     * - Identifier is Serial = New Serial was sent.
     *
     * @return Response
     */
    protected function processMissingDsaRouter()
    {
        if (RouterIdentifier::IMSI == $this->configurationManager->getRouterIdentifier()) {
            if ($this->getRouterModel()->getIMSI()) {
                $this->communicationLogManager->createLogInfo('log.routerImsiDoesntExist', ['imsi' => $this->getRouterModel()->getIMSI()]);
                // RESPONSE
                $this->getResponse()->setContent('Router with IMSI = '.$this->getRouterModel()->getIMSI().' does not exist.');
            } else {
                $this->communicationLogManager->createLogInfo('log.routerImsiEmpty', ['serial' => $this->getRouterModel()->getSerial()]);
                // RESPONSE
                $this->getResponse()->setContent('IMSI is empty. Router with Serial = '.$this->getRouterModel()->getSerial().' does not exist.');
            }
        } else {
            $this->communicationLogManager->createLogInfo('log.routerSerialDoesntExist', ['serial' => $this->getRouterModel()->getSerial()]);
            // RESPONSE
            $this->getResponse()->setContent('Router with Serial = '.$this->getRouterModel()->getSerial().' does not exist.');
        }
    }

    protected function updateLastDataInformation(): void
    {
        $this->fillCommunicationData($this->getDevice());
        $this->fillGsmData($this->getDevice(), true);
        $this->fillVersionFirmware1($this->getDevice());
        $this->fillVersionFirmware2($this->getDevice());
        $this->fillVersionFirmware3($this->getDevice());

        $this->getDevice()->setModel($this->getRouterModel()->getModel());
    }

    public function fillVersionFirmware1(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        if ($this->getRouterModel()) {
            if ($this->getDeviceType() && $this->getDeviceType()->getHasFirmware1() && $this->getRouterModel()->getFirmware()) {
                $entity->setFirmwareVersion1($this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware()));
            }
        }

        return $entity;
    }

    public function fillVersionFirmware2(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        if ($this->getRouterModel()) {
            if ($this->getDeviceType() && $this->getDeviceType()->getHasFirmware2()) {
                $entity->setFirmwareVersion2($this->getRouterModel()->getAgentVersion());
            }
        }

        return $entity;
    }

    public function fillVersionFirmware3(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface
    {
        if ($this->getRouterModel()) {
            if ($this->getDeviceType() && $this->getDeviceType()->getHasFirmware3()) {
                $entity->setFirmwareVersion3($this->getRouterModel()->getPySdkPackageVersion());
            }
        }

        return $entity;
    }
}
