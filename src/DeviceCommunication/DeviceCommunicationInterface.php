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
use App\Entity\DeviceSecret;
use App\Entity\DeviceType;
use App\Entity\Traits\CommunicationEntityInterface;
use App\Entity\Traits\FirmwareStatusEntityInterface;
use App\Entity\Traits\GsmEntityInterface;
use App\Model\FieldRequirementsModel;
use Symfony\Component\Routing\RouteCollection;

interface DeviceCommunicationInterface
{
    public function setDeviceType(?DeviceType $deviceType);

    // Should device while downloading firmware provide authentication as in regular communication
    public function isFirmwareSecured(): bool;

    // Are communication procedure  required requirements fulfilled - depending on device type configuration, license and system state
    // DeviceType has to be set in communication procedure
    public function isDeviceTypeValid(): bool;

    // Are communication procedure required requirements fulfilled- depending on license and system state
    public function isCommunicationProcedureValid(): bool;

    public function getDeviceVariables(bool $decryptSecretValues = false, bool $createLogs = true): array;

    public function getDefinedDeviceVariables(bool $createLogs = true): array;

    public function getPredefinedDeviceVariables(bool $createLogs = true): array;

    public function getDeviceSecretVariables(bool $decryptSecretValues = false, bool $createLogs = true): array;

    public function getDeviceSecretValueEncodedVariables(DeviceSecret $deviceSecret, null|string $decryptedSecretValue = null, bool $createLogs = true): array;

    public function getCommunicationProcedureRequirementsRequired(): array;

    public function getCommunicationProcedureRequirementsOptional(): array;

    public function getCommunicationProcedureCertificateCategoryRequired(): array;

    public function getCommunicationProcedureCertificateCategoryOptional(): array;

    public function getCommunicationProcedureFieldsRequirements(): FieldRequirementsModel;

    public function getDeviceTypeValidationGroups(DeviceType $deviceType): array;

    public function setDefaultFieldRequirements(DeviceType $deviceType): void;

    // DeviceCommunicationFactory will add those routes to symfony routes
    public function getRoutes(DeviceType $deviceType): RouteCollection;

    // Function generates identifier for device - it will use name, serial, imsi, uuid, or other fields depending on communication procedure specifics
    // It has to be used after filling other fields (check used communication procedure for required fields)
    public function generateIdentifier(Device $device): string;

    // CommunicationLogManager will fill {{ data }} translation variable with this value
    public function getLogData(): string;

    // CommunicationLogManager will use not null value if null content will be provided to log creation function (it is default behaviour)
    // Use this function to have content set to all log records (exept those which sets content explicitly)
    public function getLogDefaultContent(): ?string;

    // Use functions below to fill entity traits data - mainly use for CommunicationLog
    public function fillGsmData(GsmEntityInterface $entity): GsmEntityInterface;

    public function fillVersionFirmware1(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface;

    public function fillVersionFirmware2(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface;

    public function fillVersionFirmware3(FirmwareStatusEntityInterface $entity): FirmwareStatusEntityInterface;

    public function fillCommunicationData(CommunicationEntityInterface $entity): CommunicationEntityInterface;
}
