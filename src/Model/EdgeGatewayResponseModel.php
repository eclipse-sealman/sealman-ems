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

use Symfony\Component\Serializer\Annotation\Groups;

class EdgeGatewayResponseModel extends ResponseModel
{
    /**
     * Serial number.
     */
    #[Groups(['edgeGateway:register', 'edgeGateway:configuration'])]
    private ?string $serialNumber = null;

    /**
     * Config content.
     */
    #[Groups(['edgeGateway:configuration'])]
    private null|string|SerializableJson $config = null;

    /**
     * Command name.
     */
    #[Groups(['edgeGateway:configuration'])]
    private ?string $commandName = null;

    /**
     * Command transaction ID.
     */
    #[Groups(['edgeGateway:configuration'])]
    private ?string $commandTransactionId = null;

    /**
     * Firmware URL.
     */
    #[Groups(['edgeGateway:configuration'])]
    private ?string $firmwareUrl = null;

    /**
     * Error information.
     */
    #[Groups(['edgeGateway:register', 'edgeGateway:configuration'])]
    private ?string $error = null;

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber)
    {
        $this->serialNumber = $serialNumber;
    }

    public function getConfig(): null|string|SerializableJson
    {
        return $this->config;
    }

    public function setConfig(null|string|SerializableJson $config)
    {
        $this->config = $config;
    }

    public function getCommandName(): ?string
    {
        return $this->commandName;
    }

    public function setCommandName(?string $commandName)
    {
        $this->commandName = $commandName;
    }

    public function getCommandTransactionId(): ?string
    {
        return $this->commandTransactionId;
    }

    public function setCommandTransactionId(?string $commandTransactionId)
    {
        $this->commandTransactionId = $commandTransactionId;
    }

    public function getFirmwareUrl(): ?string
    {
        return $this->firmwareUrl;
    }

    public function setFirmwareUrl(?string $firmwareUrl)
    {
        $this->firmwareUrl = $firmwareUrl;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error)
    {
        $this->error = $error;
    }
}
