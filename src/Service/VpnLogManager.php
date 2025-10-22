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

namespace App\Service;

use App\Entity\Certificate;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\User;
use App\Entity\VpnConnection;
use App\Entity\VpnLog;
use App\Enum\LogLevel;
use App\Model\LogModel;
use App\Model\LogsCollection;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\VpnManagerTrait;

class VpnLogManager
{
    use EntityManagerTrait;
    use TranslatorTrait;
    use VpnManagerTrait;

    public function createLogCritical(
        string $message,
        array $messageVariables = [],
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        ?\DateTime $createdAt = new \DateTime(),
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed
    ): LogModel {
        return $this->createLog(LogLevel::CRITICAL, $message, $messageVariables, $device, $user, $vpnConnection, $endpointDevice, $certificate, $createdAt, $target);
    }

    public function createLogError(
        string $message,
        array $messageVariables = [],
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        ?\DateTime $createdAt = new \DateTime(),
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed
    ): LogModel {
        return $this->createLog(LogLevel::ERROR, $message, $messageVariables, $device, $user, $vpnConnection, $endpointDevice, $certificate, $createdAt, $target);
    }

    public function createLogWarning(
        string $message,
        array $messageVariables = [],
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        ?\DateTime $createdAt = new \DateTime(),
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed
    ): LogModel {
        return $this->createLog(LogLevel::WARNING, $message, $messageVariables, $device, $user, $vpnConnection, $endpointDevice, $certificate, $createdAt, $target);
    }

    public function createLogInfo(
        string $message,
        array $messageVariables = [],
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        ?\DateTime $createdAt = new \DateTime(),
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed
    ): LogModel {
        return $this->createLog(LogLevel::INFO, $message, $messageVariables, $device, $user, $vpnConnection, $endpointDevice, $certificate, $createdAt, $target);
    }

    public function createLogDebug(
        string $message,
        array $messageVariables = [],
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        ?\DateTime $createdAt = new \DateTime(),
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed
    ): LogModel {
        return $this->createLog(LogLevel::DEBUG, $message, $messageVariables, $device, $user, $vpnConnection, $endpointDevice, $certificate, $createdAt, $target);
    }

    public function createLogs(
        LogsCollection $logsCollection,
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed): LogsCollection
    ): LogsCollection {
        foreach ($logsCollection as $logModel) {
            $updatedMessageVariables = $this->getMessageVariables($logModel->getMessageVariables(), $device, $user, $vpnConnection, $endpointDevice, $certificate, $target);

            $this->createLogWithMessageVariables(
                    $logModel->getLogLevel(),
                    $logModel->getMessage(),
                    $updatedMessageVariables,
                    $device,
                    $user,
                    $vpnConnection,
                    $endpointDevice,
                    $certificate,
                    $logModel->getCreatedAt(),
                    $target
                );

            $logModel->setMessageVariables($updatedMessageVariables);
        }

        $this->entityManager->flush();

        return $logsCollection;
    }

    public function createLog(
        LogLevel $logLevel,
        string $message,
        array $messageVariables = [],
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        ?\DateTime $createdAt = new \DateTime(),
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed
    ): LogModel {
        $updatedMessageVariables = $this->getMessageVariables($messageVariables, $device, $user, $vpnConnection, $endpointDevice, $certificate, $target);

        $logModel = $this->createLogWithMessageVariables($logLevel, $message, $updatedMessageVariables, $device, $user, $vpnConnection, $endpointDevice, $certificate, $createdAt, $target);

        $this->entityManager->flush();

        return $logModel;
    }

    protected function createLogWithMessageVariables(
        LogLevel $logLevel,
        string $message,
        array $messageVariables = [],
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        ?\DateTime $createdAt = new \DateTime(),
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed
    ): LogModel {
        if ($target instanceof Device && !$device) {
            $device = $target;
        }
        if ($target instanceof User && !$user) {
            $user = $target;
        }
        if ($target instanceof VpnConnection && !$vpnConnection) {
            $vpnConnection = $target;
        }
        if ($target instanceof DeviceEndpointDevice && !$endpointDevice) {
            $endpointDevice = $target;
        }
        if ($target instanceof Certificate && !$certificate) {
            $certificate = $target;
        }

        $vpnLog = new VpnLog();

        $logModel = new LogModel($logLevel, $message, $messageVariables);

        $translatedMessage = $this->trans($message, $messageVariables);

        if ($createdAt) {
            $vpnLog->setCreatedAt($createdAt);
            $logModel->setCreatedAt($createdAt);
        }

        $vpnLog->setUser($user);
        $vpnLog->setEndpointDevice($endpointDevice);
        $vpnLog->setDevice($device);

        if ($certificate) {
            if ($certificate->getTarget() instanceof Device && !$vpnLog->getDevice()) {
                $vpnLog->setDevice($certificate->getDevice());
            }
            if ($certificate->getTarget() instanceof User && !$vpnLog->getUser()) {
                $vpnLog->setUser($certificate->getUser());
            }
        }

        if ($vpnConnection) {
            if ($vpnConnection->getUser() && !$vpnLog->getUser()) {
                $vpnLog->setUser($vpnConnection->getUser());
            }
            if ($vpnConnection->getTarget() instanceof Device && !$vpnLog->getDevice()) {
                $vpnLog->setDevice($vpnConnection->getDevice());
            }
            if ($vpnConnection->getTarget() instanceof DeviceEndpointDevice && !$vpnLog->getEndpointDevice()) {
                $vpnLog->setEndpointDevice($vpnConnection->getEndpointDevice());
            }
        }

        $vpnLog->setLogLevel($logLevel);
        $vpnLog->setMessage($translatedMessage);

        $this->entityManager->persist($vpnLog);

        return $logModel;
    }

    protected function getMessageVariables(
        array $messageVariables = [],
        ?Device $device = null,
        ?User $user = null,
        ?VpnConnection $vpnConnection = null,
        ?DeviceEndpointDevice $endpointDevice = null,
        ?Certificate $certificate = null,
        Device|DeviceEndpointDevice|Certificate|User|null $target = null // kept for now - if proved to be not needed will be removed
    ): array {
        if ($target instanceof Device && !$device) {
            $device = $target;
        }
        if ($target instanceof User && !$user) {
            $user = $target;
        }
        if ($target instanceof VpnConnection && !$vpnConnection) {
            $vpnConnection = $target;
        }
        if ($target instanceof DeviceEndpointDevice && !$endpointDevice) {
            $endpointDevice = $target;
        }
        if ($target instanceof Certificate && !$certificate) {
            $certificate = $target;
        }

        if ($vpnConnection) {
            if (!$user) {
                $user = $vpnConnection->getUser();
            }
            if ($vpnConnection->getTarget()) {
                if ($vpnConnection->getTarget() instanceof Device && !$device) {
                    $device = $vpnConnection->getTarget();
                }
                if ($vpnConnection->getTarget() instanceof DeviceEndpointDevice && !$endpointDevice) {
                    $endpointDevice = $vpnConnection->getTarget();
                }
            }
        }

        if ($certificate) {
            if ($certificate->getTarget()) {
                if ($certificate->getTarget() instanceof Device && !$device) {
                    $device = $certificate->getTarget();
                }
                if ($certificate->getTarget() instanceof User && !$user) {
                    $user = $certificate->getTarget();
                }
            }
        }

        $translateMessageVariables = [];

        $translateMessageVariables['{{ identifier }}'] = $this->trans('functionality.nullObjectIdentifier');
        $translateMessageVariables['{{ name }}'] = $this->trans('functionality.nullObjectIdentifier');
        $translateMessageVariables['{{ deviceType }}'] = $this->trans('functionality.nullObjectLabel');
        $translateMessageVariables['{{ deviceName }}'] = $this->trans('functionality.nullObjectLabel');

        // Sequence $user, $endpointDevice, $device - required by $vpnConnection and $certificate
        if ($user) {
            $translateMessageVariables['{{ identifier }}'] = $user->getRepresentation();
            $translateMessageVariables['{{ name }}'] = $user->getRepresentation();
            $translateMessageVariables['{{ userName }}'] = $user->getRepresentation();
            $translateMessageVariables['{{ deviceType }}'] = $this->trans('functionality.userObjectLabel');
            $translateMessageVariables['{{ deviceName }}'] = $this->trans('functionality.userObjectLabel');
        }

        if ($endpointDevice) {
            $translateMessageVariables['{{ identifier }}'] = $endpointDevice->getRepresentation();
            $translateMessageVariables['{{ name }}'] = $endpointDevice->getName();
            $translateMessageVariables['{{ deviceType }}'] = $endpointDevice->getDevice()->getDeviceType()->getName();
            $translateMessageVariables['{{ deviceName }}'] = $endpointDevice->getDevice()->getDeviceType()->getDeviceName();
        }

        if ($device) {
            $translateMessageVariables['{{ identifier }}'] = $device->getRepresentation();
            $translateMessageVariables['{{ name }}'] = $device->getName();
            $translateMessageVariables['{{ deviceType }}'] = $device->getDeviceType()->getName();
            $translateMessageVariables['{{ deviceName }}'] = $device->getDeviceType()->getDeviceName();
        }

        if ($certificate) {
            $translateMessageVariables['{{ certificateType }}'] = $certificate->getCertificateType()->getRepresentation();
            $translateMessageVariables['{{ certificateSubject }}'] = $certificate->getCertificateSubject() ?: 'N/A';
            $translateMessageVariables['{{ certificateCaSubject }}'] = $certificate->getCertificateCaSubject() ?: 'N/A';
        }

        if ($vpnConnection) {
            if ($vpnConnection->getUser()) {
                $translateMessageVariables['{{ userName }}'] = $vpnConnection->getUser()->getRepresentation();
                $translateMessageVariables['{{ userVpnClientIp }}'] = $vpnConnection->getUser()->getVpnIp();
            }

            if ($vpnConnection->getTarget()) {
                $translateMessageVariables['{{ destinationIp }}'] = $this->vpnManager->getVpnClientIp($vpnConnection->getTarget());
            }

            $translateMessageVariables['{{ sourceNetwork }}'] = $vpnConnection->getSource() ?: 'N/A';
            $translateMessageVariables['{{ destinationNetwork }}'] = $vpnConnection->getDestination() ?: 'N/A';
        }

        $processedMessageVariables = [];
        foreach ($messageVariables as $messageVariableName => $messageVariableValue) {
            // Processing variable names from "variableName" to "{{ variableName }}" for convenience
            $processedMessageVariables['{{ '.$messageVariableName.' }}'] = $messageVariableValue;
        }

        $translateMessageVariables = array_merge($translateMessageVariables, $processedMessageVariables);

        return $translateMessageVariables;
    }
}
