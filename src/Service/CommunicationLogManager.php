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

use App\DeviceCommunication\DeviceCommunicationInterface;
use App\Entity\CommunicationLog;
use App\Entity\CommunicationLogContent;
use App\Entity\ConfigLog;
use App\Entity\ConfigLogContent;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Entity\DiagnoseLog;
use App\Entity\DiagnoseLogContent;
use App\Enum\Feature;
use App\Enum\LogLevel;
use App\Model\DiagnoseLogModel;
use App\Model\ResponseModel;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\ViewHandlerTrait;
use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CommunicationLogManager
{
    use EncryptionManagerTrait;
    use EntityManagerTrait;
    use TranslatorTrait;
    use ViewHandlerTrait;

    /**
     * Currently connected deviceType.
     *
     * @var ?DeviceType
     */
    protected $deviceType = null;

    public function getDeviceType(): ?DeviceType
    {
        return $this->deviceType;
    }

    public function setDeviceType(?DeviceType $deviceType)
    {
        $this->deviceType = $deviceType;
    }

    /**
     * Currently connected device.
     *
     * @var ?Device
     */
    protected $device = null;

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }

    /**
     * Currently used deviceCommunication.
     *
     * @var ?DeviceCommunicationInterface
     */
    protected $deviceCommunication = null;

    public function getDeviceCommunication(): ?DeviceCommunicationInterface
    {
        return $this->deviceCommunication;
    }

    public function setDeviceCommunication(?DeviceCommunicationInterface $deviceCommunication)
    {
        $this->deviceCommunication = $deviceCommunication;
    }

    /**
     * Currently connected request.
     *
     * @var ?Request
     */
    protected $request = null;

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request)
    {
        $this->request = $request;
    }

    public function clearRequest(): void
    {
        $this->setRequest(null);
    }

    /**
     * Logs created without device assigned.
     *
     * @var array
     */
    protected $logsWithoutDevice = [];

    public function getLogsWithoutDevice(): array
    {
        return $this->logsWithoutDevice;
    }

    public function addLogWithoutDevice(CommunicationLog $log)
    {
        $this->logsWithoutDevice[] = $log;
    }

    public function clearLogsWithoutDevice(): void
    {
        $this->logsWithoutDevice = [];
    }

    /**
     * Logs created - to be filled with accessTags.
     *
     * @var array
     */
    protected $logsWithoutAccessTags = [];

    public function getLogsWithoutAccessTags(): array
    {
        return $this->logsWithoutAccessTags;
    }

    public function addLogWithoutAccessTags(CommunicationLog $log)
    {
        $this->logsWithoutAccessTags[] = $log;
    }

    public function clearLogsWithoutAccessTags(): void
    {
        $this->logsWithoutAccessTags = [];
    }

    /**
     * Diagnose Logs created - to be filled with accessTags.
     *
     * @var array
     */
    protected $diagnoseLogsWithoutAccessTags = [];

    public function getDiagnoseLogsWithoutAccessTags(): array
    {
        return $this->diagnoseLogsWithoutAccessTags;
    }

    public function addDiagnoseLogWithoutAccessTags(DiagnoseLog $diagnoseLog)
    {
        $this->diagnoseLogsWithoutAccessTags[] = $diagnoseLog;
    }

    public function clearDiagnoseLogsWithoutAccessTags(): void
    {
        $this->diagnoseLogsWithoutAccessTags = [];
    }

    /**
     * Config Logs created - to be filled with accessTags.
     *
     * @var array
     */
    protected $configLogsWithoutAccessTags = [];

    public function getConfigLogsWithoutAccessTags(): array
    {
        return $this->configLogsWithoutAccessTags;
    }

    public function addConfigLogWithoutAccessTags(ConfigLog $configLog)
    {
        $this->configLogsWithoutAccessTags[] = $configLog;
    }

    public function clearConfigLogsWithoutAccessTags(): void
    {
        $this->configLogsWithoutAccessTags = [];
    }

    public function handleConfigLog(?string $content, Feature $feature = Feature::PRIMARY): CommunicationLog
    {
        if ($content) {
            $currentConfigLogMd5 = md5($content);
            $previousRouterConfigLog = $this->getDevice()->getConfigLogs()->last();

            if ($previousRouterConfigLog && $previousRouterConfigLog->getMd5() === $currentConfigLogMd5) {
                // configuration has not been changed since last communication - update only date in previous configuration
                $previousRouterConfigLog->setUpdatedAt(new \DateTime());

                $this->entityManager->persist($previousRouterConfigLog);

                return $this->createLogInfo('log.configSameAsPreviousOne');
            } else {
                $communicationLog = $this->createLogInfo('log.configDifferentThanPreviousOne');

                $configLog = new ConfigLog();
                $configLog->setLogLevel(LogLevel::INFO);

                if ($content) {
                    $content = $this->encryptionManager->encrypt($content);

                    $configLogContent = new ConfigLogContent();
                    $configLogContent->setContent($content);
                    $configLogContent->setConfigLog($configLog);

                    $this->entityManager->persist($configLogContent);

                    $configLog->setConfigLogContent($configLogContent);
                }

                $configLog->setFeature($feature);
                $configLog->setCommunicationLog($communicationLog);
                $configLog->setDevice($this->getDevice());
                if ($this->getDevice()) {
                    $configLog->setDeviceType($this->getDevice()->getDeviceType());
                }
                $configLog->setMd5($currentConfigLogMd5);

                $this->entityManager->persist($configLog);

                $this->addConfigLogWithoutAccessTags($configLog);

                return $communicationLog;
            }
        }

        return $this->createLogInfo('log.noConfigSent');
    }

    public function handleDiagnoseLog(?string $content): CommunicationLog
    {
        if ($content) {
            $diagnoseLog = new DiagnoseLog();
            $diagnoseLog->setLogLevel(LogLevel::INFO);
            $diagnoseLog->setDevice($this->getDevice());
            if ($this->getDevice()) {
                $diagnoseLog->setDeviceType($this->getDevice()->getDeviceType());
            }

            if ($content) {
                $content = $this->encryptionManager->encrypt($content);

                $diagnoseLogContent = new DiagnoseLogContent();
                $diagnoseLogContent->setContent($content);
                $diagnoseLogContent->setDiagnoseLog($diagnoseLog);

                $this->entityManager->persist($diagnoseLogContent);

                $diagnoseLog->setDiagnoseLogContent($diagnoseLogContent);
            }

            $this->entityManager->persist($diagnoseLog);

            $this->addDiagnoseLogWithoutAccessTags($diagnoseLog);

            return $this->createLogInfo('log.diagnoseDataSent');
        }

        return $this->createLogInfo('log.noDiagnoseDataSent');
    }

    public function handleDiagnoseLogModel(Collection|array $diagnoseLogModel): CommunicationLog
    {
        if (\is_array($diagnoseLogModel)) {
            if (count($diagnoseLogModel) <= 0) {
                return $this->createLogInfo('log.noDiagnoseDataSent');
            }
        } else {
            if ($diagnoseLogModel->count() <= 0) {
                return $this->createLogInfo('log.noDiagnoseDataSent');
            }
        }

        $concatenatedDiagnoseLog = '';
        $logLevel = LogLevel::DEBUG;

        foreach ($diagnoseLogModel as $log) {
            if ($log instanceof DiagnoseLogModel) {
                $logLevel = $this->getHigherLogLevel($logLevel, $log->getLogLevel());

                if ($log->getMessage()) {
                    if ('' !== $concatenatedDiagnoseLog) {
                        $concatenatedDiagnoseLog .= "\n";
                    }

                    if ($log->getLogAt()) {
                        $concatenatedDiagnoseLog .= $log->getLogAt()->format('Y-m-d H:i:s');
                    } else {
                        $concatenatedDiagnoseLog .= 'N/A';
                    }

                    $concatenatedDiagnoseLog .= ' ['.$log->getLogLevel()->value.']';
                    $concatenatedDiagnoseLog .= ': '.$log->getMessage();
                }
            }
        }

        $diagnoseLog = new DiagnoseLog();
        $diagnoseLog->setLogLevel($logLevel);
        $diagnoseLog->setDevice($this->getDevice());
        if ($this->getDevice()) {
            $diagnoseLog->setDeviceType($this->getDevice()->getDeviceType());
        }

        if ('' !== $concatenatedDiagnoseLog) {
            $content = $this->encryptionManager->encrypt($concatenatedDiagnoseLog);

            $diagnoseLogContent = new DiagnoseLogContent();
            $diagnoseLogContent->setContent($content);
            $diagnoseLogContent->setDiagnoseLog($diagnoseLog);

            $this->entityManager->persist($diagnoseLogContent);

            $diagnoseLog->setDiagnoseLogContent($diagnoseLogContent);
        }

        $this->entityManager->persist($diagnoseLog);

        $this->addDiagnoseLogWithoutAccessTags($diagnoseLog);

        return $this->createLogInfo('log.diagnoseLogSent');
    }

    public function getHigherLogLevel(LogLevel $loglevel1, LogLevel $loglevel2): LogLevel
    {
        $orderedLogLevels = [
            LogLevel::DEBUG,
            LogLevel::INFO,
            LogLevel::WARNING,
            LogLevel::ERROR,
            LogLevel::CRITICAL,
        ];

        $loglevel1Index = array_search($loglevel1, $orderedLogLevels);
        $loglevel2Index = array_search($loglevel2, $orderedLogLevels);

        if ($loglevel1Index > $loglevel2Index) {
            return $loglevel1;
        }

        return $loglevel2;
    }

    public function createSystemUserDoesNotExist(Request $request): CommunicationLog
    {
        return $this->createLogCritical('log.systemUserDoesNotExist', [], $request->__toString());
    }

    public function createDeviceRequestIncoming(Request $request): CommunicationLog
    {
        return $this->createLogDebug('log.requestDebug', [], $request->__toString());
    }

    public function createDeviceResponseOutgoing(Response $response): CommunicationLog
    {
        return $this->createLogDebug('log.responseDebug', [], $response->__toString());
    }

    public function createDeviceResponseViewOutgoing(ResponseModel $responseModel): Response
    {
        if (!$this->getRequest()) {
            $this->createLogCritical('log.requestIsMissing');

            return new Response();
        }

        $response = $this->viewHandler->createResponse($this->getAnnotatedView($this->getRequest(), $responseModel), $this->getRequest(), 'json');

        $this->createLogDebug('log.responseDebug', [], $response->__toString());

        return $response;
    }

    public function createLogCritical(string $message, array $messageVariables = [], ?string $content = null, ?Device $device = null, bool $translate = true, bool $processVariables = true, bool $fillDeviceData = true): CommunicationLog
    {
        return $this->createLog(LogLevel::CRITICAL, $message, $messageVariables, $content, $device, $translate, $processVariables, $fillDeviceData);
    }

    public function createLogError(string $message, array $messageVariables = [], ?string $content = null, ?Device $device = null, bool $translate = true, bool $processVariables = true, bool $fillDeviceData = true): CommunicationLog
    {
        return $this->createLog(LogLevel::ERROR, $message, $messageVariables, $content, $device, $translate, $processVariables, $fillDeviceData);
    }

    public function createLogWarning(string $message, array $messageVariables = [], ?string $content = null, ?Device $device = null, bool $translate = true, bool $processVariables = true, bool $fillDeviceData = true): CommunicationLog
    {
        return $this->createLog(LogLevel::WARNING, $message, $messageVariables, $content, $device, $translate, $processVariables, $fillDeviceData);
    }

    public function createLogInfo(string $message, array $messageVariables = [], ?string $content = null, ?Device $device = null, bool $translate = true, bool $processVariables = true, bool $fillDeviceData = true): CommunicationLog
    {
        return $this->createLog(LogLevel::INFO, $message, $messageVariables, $content, $device, $translate, $processVariables, $fillDeviceData);
    }

    public function createLogDebug(string $message, array $messageVariables = [], ?string $content = null, ?Device $device = null, bool $translate = true, bool $processVariables = true, bool $fillDeviceData = true): CommunicationLog
    {
        return $this->createLog(LogLevel::DEBUG, $message, $messageVariables, $content, $device, $translate, $processVariables, $fillDeviceData);
    }

    public function createLog(LogLevel $logLevel, string $message, array $messageVariables = [], ?string $content = null, ?Device $device = null, bool $translate = true, bool $processVariables = true, bool $fillDeviceData = true): CommunicationLog
    {
        if (null == $device) {
            $device = $this->getDevice();
        }

        if ($translate) {
            // Presetting parameters for nicer logs
            $translateMessageVariables = [
                '{{ identifier }}' => 'N/A',
                '{{ name }}' => 'N/A',
                '{{ data }}' => 'N/A',
                '{{ deviceType }}' => 'N/A',
                '{{ deviceName }}' => 'N/A',
                '{{ communicationProcedure }}' => 'N/A',
                '{{ routePrefix }}' => 'N/A',
                '{{ nameFirmware1 }}' => 'N/A',
                '{{ nameFirmware2 }}' => 'N/A',
                '{{ nameFirmware3 }}' => 'N/A',
                '{{ nameConfig1 }}' => 'N/A',
                '{{ nameConfig2 }}' => 'N/A',
                '{{ nameConfig3 }}' => 'N/A',
            ];

            if ($this->getDeviceCommunication()) {
                $translateMessageVariables['{{ data }}'] = $this->getDeviceCommunication()->getLogData();
            }

            if ($this->getDeviceType()) {
                $translateMessageVariables['{{ deviceType }}'] = $this->getDeviceType()->getName();
                $translateMessageVariables['{{ deviceName }}'] = $this->getDeviceType()->getDeviceName();
                $translateMessageVariables['{{ communicationProcedure }}'] = $this->getDeviceType()->getCommunicationProcedure() ? $this->getDeviceType()->getCommunicationProcedure()->value : 'N/A';
                $translateMessageVariables['{{ routePrefix }}'] = $this->getDeviceType()->getRoutePrefix();
                $translateMessageVariables['{{ nameFirmware1 }}'] = $this->getDeviceType()->getNameFirmware1();
                $translateMessageVariables['{{ nameFirmware2 }}'] = $this->getDeviceType()->getNameFirmware2();
                $translateMessageVariables['{{ nameFirmware3 }}'] = $this->getDeviceType()->getNameFirmware3();
                $translateMessageVariables['{{ nameConfig1 }}'] = $this->getDeviceType()->getNameConfig1();
                $translateMessageVariables['{{ nameConfig2 }}'] = $this->getDeviceType()->getNameConfig2();
                $translateMessageVariables['{{ nameConfig3 }}'] = $this->getDeviceType()->getNameConfig3();
            }
            if ($device) {
                $translateMessageVariables['{{ identifier }}'] = $device->getIdentifier();
                $translateMessageVariables['{{ name }}'] = $device->getName();
            }

            if ($processVariables) {
                $processedMessageVariables = [];
                foreach ($messageVariables as $messageVariableName => $messageVariableValue) {
                    // Processing variable names from "variableName" to "{{ variableName }}" for convenience
                    $processedMessageVariables['{{ '.$messageVariableName.' }}'] = $messageVariableValue;
                }
            } else {
                $processedMessageVariables = $messageVariables;
            }

            $translateMessageVariables = array_merge($translateMessageVariables, $processedMessageVariables);
            $translatedMessage = $this->trans($message, $translateMessageVariables);
        } else {
            $translatedMessage = $message;
        }

        $processedContent = null;
        if (null === $content) {
            if ($this->getDeviceCommunication()) {
                $processedContent = $this->getDeviceCommunication()->getLogDefaultContent();
            }
        } else {
            $processedContent = $content;
        }

        $communicationLog = new CommunicationLog();
        $communicationLog->setLogLevel($logLevel);
        $communicationLog->setMessage($translatedMessage);

        if ($processedContent) {
            $processedContent = $this->encryptionManager->encrypt($processedContent);

            $communicationLogContent = new CommunicationLogContent();
            $communicationLogContent->setContent($processedContent);
            $communicationLogContent->setCommunicationLog($communicationLog);

            $this->entityManager->persist($communicationLogContent);

            $communicationLog->setCommunicationLogContent($communicationLogContent);
        }

        $communicationLog->setDevice($device);

        if ($device) {
            $communicationLog->setDeviceType($device->getDeviceType());
        }

        if ($fillDeviceData && $this->getDeviceCommunication() && $this->getDeviceType()) {
            if ($this->getDeviceType()->getHasGsm()) {
                $this->getDeviceCommunication()->fillGsmData($communicationLog); // will it transfer data to object or $communicationLog =
            }
            if ($this->getDeviceType()->getHasFirmware1()) {
                $this->getDeviceCommunication()->fillVersionFirmware1($communicationLog);
            }
            if ($this->getDeviceType()->getHasFirmware2()) {
                $this->getDeviceCommunication()->fillVersionFirmware2($communicationLog);
            }
            if ($this->getDeviceType()->getHasFirmware3()) {
                $this->getDeviceCommunication()->fillVersionFirmware3($communicationLog);
            }
            $this->getDeviceCommunication()->fillCommunicationData($communicationLog);
        }

        $this->entityManager->persist($communicationLog);

        $this->addLogWithoutAccessTags($communicationLog);

        if (!$device) {
            $this->addLogWithoutDevice($communicationLog);
        }

        return $communicationLog;
    }

    // Methods makes sure that logs generated before received form is validated and device found or created, will still be assigned to correct device
    public function updateCommunicationLogsWithoutDevice(?Device $device = null)
    {
        if (null == $device) {
            $device = $this->getDevice();
        }

        if (null == $device) {
            return;
        }

        foreach ($this->getLogsWithoutDevice() as $log) {
            $log->setDevice($device);

            $log->setDeviceType($device->getDeviceType());

            $this->entityManager->persist($log);
        }

        $this->clearLogsWithoutDevice();
    }

    // Methods fills access tags for all logs using insert SQL statement to limit memory usage
    public function fillLogsWithAccessTags()
    {
        $this->fillCommunicationLogsWithAccessTags();
        $this->fillDiagnoseLogsWithAccessTags();
        $this->fillConfigLogsWithAccessTags();
    }

    // Methods fills access tags for all communication logs using insert SQL statement to limit memory usage
    public function fillCommunicationLogsWithAccessTags()
    {
        $logIds = [];
        foreach ($this->getLogsWithoutAccessTags() as $log) {
            if (!$log->getDevice()) {
                continue;
            }
            // Custom string casting to avoid SQL statement preparing issues
            $logIds[] = ''.$log->getId();
        }

        $sql = 'INSERT INTO communication_log_access_tag (communication_log_id, access_tag_id) SELECT c.id, dat.access_tag_id FROM communication_log c JOIN device d ON d.id=c.device_id JOIN device_access_tag dat ON d.id=dat.device_id WHERE c.id IN (:communicationLogId)';

        $this->entityManager->getConnection()->executeStatement(
            $sql,
            ['communicationLogId' => $logIds],
            ['communicationLogId' => ArrayParameterType::INTEGER]
        );

        $this->clearLogsWithoutAccessTags();
    }

    // Methods fills access tags for all diagnose logs using insert SQL statement to limit memory usage
    public function fillDiagnoseLogsWithAccessTags()
    {
        $logIds = [];
        foreach ($this->getDiagnoseLogsWithoutAccessTags() as $log) {
            if (!$log->getDevice()) {
                continue;
            }
            // Custom string casting to avoid SQL statement preparing issues
            $logIds[] = ''.$log->getId();
        }

        $sql = 'INSERT INTO diagnose_log_access_tag (diagnose_log_id, access_tag_id) SELECT c.id, dat.access_tag_id FROM diagnose_log c JOIN device d ON d.id=c.device_id JOIN device_access_tag dat ON d.id=dat.device_id WHERE c.id IN (:diagnoseLogId)';

        $this->entityManager->getConnection()->executeStatement(
            $sql,
            ['diagnoseLogId' => $logIds],
            ['diagnoseLogId' => ArrayParameterType::INTEGER]
        );

        $this->clearDiagnoseLogsWithoutAccessTags();
    }

    // Methods fills access tags for all config logs using insert SQL statement to limit memory usage
    public function fillConfigLogsWithAccessTags()
    {
        $logIds = [];
        foreach ($this->getConfigLogsWithoutAccessTags() as $log) {
            if (!$log->getDevice()) {
                continue;
            }
            // Custom string casting to avoid SQL statement preparing issues
            $logIds[] = ''.$log->getId();
        }

        $sql = 'INSERT INTO config_log_access_tag (config_log_id, access_tag_id) SELECT c.id, dat.access_tag_id FROM config_log c JOIN device d ON d.id=c.device_id JOIN device_access_tag dat ON d.id=dat.device_id WHERE c.id IN (:configLogId)';

        $this->entityManager->getConnection()->executeStatement(
            $sql,
            ['configLogId' => $logIds],
            ['configLogId' => ArrayParameterType::INTEGER]
        );

        $this->clearConfigLogsWithoutAccessTags();
    }
}
