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

namespace App\Model;

use App\Enum\EdgeGatewayCommandName;
use App\Enum\EdgeGatewayCommandStatus;
use Carve\ApiBundle\Validator\Constraints as Assert;

class EdgeGatewayModel
{
    /**
     * Registration ID.
     */
    #[Assert\NotBlank(groups: ['fieldRegistrationIdRequired'])]
    private ?string $registrationId = null;

    /**
     * Endorsement key.
     */
    #[Assert\NotBlank(groups: ['fieldEndorsementKeyRequired'])]
    private ?string $endorsementKey = null;

    /**
     * Hardware version.
     */
    #[Assert\NotBlank(groups: ['fieldHardwareVersionRequired'])]
    #[Assert\Length(max: 255)]
    private ?string $hardwareVersion = null;

    /**
     * Firmware version.
     */
    #[Assert\NotBlank(groups: ['edgeGatewayConfiguration'])]
    #[Assert\Length(max: 255)]
    private ?string $firmwareVersion = null;

    /**
     * Serial number.
     */
    #[Assert\NotBlank(groups: ['fieldSerialNumberRequired'])]
    #[Assert\Length(max: 255, groups: ['Default', 'authentication'])]
    private ?string $serialNumber = null;

    /**
     * IMSI.
     */
    #[Assert\NotBlank(groups: ['fieldImsiRequired'])]
    #[Assert\Length(max: 255)]
    private ?string $imsi = null;

    /**
     * IMEI.
     */
    #[Assert\Length(max: 255)]
    private ?string $imei = null;

    /**
     * Network generation.
     */
    #[Assert\Length(max: 255)]
    private ?string $networkGeneration = null;

    /**
     * Command name.
     */
    private ?EdgeGatewayCommandName $commandName = null;

    /**
     * Command status.
     */
    private ?EdgeGatewayCommandStatus $commandStatus = null;

    /**
     * Command transaction ID.
     */
    #[Assert\Length(max: 255)]
    private ?string $commandTransactionId = null;

    /**
     * Command status error category.
     */
    #[Assert\Length(max: 255)]
    private ?string $commandStatusErrorCategory = null;

    /**
     * Command status error PID.
     */
    #[Assert\Length(max: 255)]
    private ?string $commandStatusErrorPid = null;

    /**
     * Command status error message.
     */
    private ?string $commandStatusErrorMessage = null;

    /**
     * Config content.
     */
    private ?array $config = null;

    public function getRegistrationId(): ?string
    {
        return $this->registrationId;
    }

    public function setRegistrationId(?string $registrationId)
    {
        $this->registrationId = $registrationId;
    }

    public function getEndorsementKey(): ?string
    {
        return $this->endorsementKey;
    }

    public function setEndorsementKey(?string $endorsementKey)
    {
        $this->endorsementKey = $endorsementKey;
    }

    public function getHardwareVersion(): ?string
    {
        return $this->hardwareVersion;
    }

    public function setHardwareVersion(?string $hardwareVersion)
    {
        $this->hardwareVersion = $hardwareVersion;
    }

    public function getFirmwareVersion(): ?string
    {
        return $this->firmwareVersion;
    }

    public function setFirmwareVersion(?string $firmwareVersion)
    {
        $this->firmwareVersion = $firmwareVersion;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber)
    {
        $this->serialNumber = $serialNumber;
    }

    public function getImsi(): ?string
    {
        return $this->imsi;
    }

    public function setImsi(?string $imsi)
    {
        $this->imsi = $imsi;
    }

    public function getImei(): ?string
    {
        return $this->imei;
    }

    public function setImei(?string $imei)
    {
        $this->imei = $imei;
    }

    public function getNetworkGeneration(): ?string
    {
        return $this->networkGeneration;
    }

    public function setNetworkGeneration(?string $networkGeneration)
    {
        $this->networkGeneration = $networkGeneration;
    }

    public function getCommandName(): ?EdgeGatewayCommandName
    {
        return $this->commandName;
    }

    public function setCommandName(?EdgeGatewayCommandName $commandName)
    {
        $this->commandName = $commandName;
    }

    public function getCommandStatus(): ?EdgeGatewayCommandStatus
    {
        return $this->commandStatus;
    }

    public function setCommandStatus(?EdgeGatewayCommandStatus $commandStatus)
    {
        $this->commandStatus = $commandStatus;
    }

    public function getCommandTransactionId(): ?string
    {
        return $this->commandTransactionId;
    }

    public function setCommandTransactionId(?string $commandTransactionId)
    {
        $this->commandTransactionId = $commandTransactionId;
    }

    public function getCommandStatusErrorCategory(): ?string
    {
        return $this->commandStatusErrorCategory;
    }

    public function setCommandStatusErrorCategory(?string $commandStatusErrorCategory)
    {
        $this->commandStatusErrorCategory = $commandStatusErrorCategory;
    }

    public function getCommandStatusErrorPid(): ?string
    {
        return $this->commandStatusErrorPid;
    }

    public function setCommandStatusErrorPid(?string $commandStatusErrorPid)
    {
        $this->commandStatusErrorPid = $commandStatusErrorPid;
    }

    public function getCommandStatusErrorMessage(): ?string
    {
        return $this->commandStatusErrorMessage;
    }

    public function setCommandStatusErrorMessage(?string $commandStatusErrorMessage)
    {
        $this->commandStatusErrorMessage = $commandStatusErrorMessage;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config)
    {
        $this->config = $config;
    }
}
