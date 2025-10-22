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
use App\Entity\Config;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\Feature;
use App\Model\ConfigDevice;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouterCommunication extends RouterOneConfigCommunication implements RouterCommunicationInterface
{
    public const CONFIG_TYPE_RUNNING = 'configuration/running-config';

    public function getRoutes(DeviceType $deviceType): RouteCollection
    {
        $routeCollection = new RouteCollection();

        $path = $deviceType->getValidRoutePrefix().'/config';
        $defaults = [
                '_controller' => 'App\Controller\DeviceCommunication\RouterController::configAction',
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
            CommunicationProcedureRequirement::HAS_CONFIG2,
            CommunicationProcedureRequirement::HAS_ALWAYS_REINSTALL_CONFIG2,
            CommunicationProcedureRequirement::HAS_TEMPLATES,
        ];

        return $requirements;
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

                // If not reinstalling firmware, try to send StartupConfig or RunningConfig
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

                    $reinstallConfig1 = $this->processReinstallConfig(Feature::PRIMARY, $this->getRouterModel()->getConfig(), false);
                    $reinstallConfig2 = false;

                    if (!$reinstallConfig1) {
                        $reinstallConfig2 = $this->processReinstallConfig(Feature::SECONDARY, $this->getRouterModel()->getConfig(), false);
                    }

                    if (!$reinstallConfig2 && !$reinstallConfig1) {
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

    protected function handleReinstallConfig(Feature $feature, ConfigDevice $generatedConfigDevice): void
    {
        $firmwareVersion = $this->normalizeFirmwareVersion($this->getRouterModel()->getFirmware());

        if (Feature::PRIMARY == $feature) {
            $contentType = self::CONFIG_TYPE_STARTUP;
            $this->getDevice()->setReinstallConfig1(false);
        }
        if (Feature::SECONDARY == $feature) {
            $contentType = self::CONFIG_TYPE_RUNNING;
            $this->getDevice()->setReinstallConfig2(false);
        }

        $this->getResponse()->headers->set('Content-Type', $this->normalizeContentType($contentType, $firmwareVersion));
        $this->getResponse()->setContent($generatedConfigDevice->getConfigGenerated());
    }

    protected function handleNoChangeResponse(): void
    {
        // Just empty response in this case
    }
}
