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

use App\Entity\Config;
use App\Enum\ConfigDeviceStatus;
use App\Enum\ConfigGenerator;
use Symfony\Component\Serializer\Annotation\Groups;

class ConfigDevice
{
    /**
     * Config object.
     */
    private ?Config $config = null;

    /**
     * Config generator.
     */
    private ?ConfigGenerator $generator = null;

    /**
     * Device config status.
     */
    #[Groups(['config:configDeviceGenerated'])]
    private ?ConfigDeviceStatus $status = ConfigDeviceStatus::NEW;

    /**
     * Config template (before being generated).
     */
    private ?string $configTemplate = null;

    /**
     * Config variables.
     */
    private ?array $variables = null;

    /**
     * Config (after being generated).
     */
    #[Groups(['config:configDeviceGenerated'])]
    private ?string $configGenerated = null;

    /**
     * Error message (translated).
     */
    #[Groups(['config:configDeviceGenerated'])]
    private ?string $errorMessage = null;

    /**
     * Error message template (not translated)
     * i.e. "configManager.configDevice.edgeGateway.error".
     */
    private ?string $errorMessageTemplate = null;

    /**
     *  Error message template variables.
     */
    private ?array $errorMessageTemplateVariables = null;

    public function isNew(): bool
    {
        return ConfigDeviceStatus::NEW === $this->getStatus();
    }

    public function isGenerated(): bool
    {
        return ConfigDeviceStatus::GENERATED === $this->getStatus();
    }

    public function isError(): bool
    {
        return ConfigDeviceStatus::ERROR === $this->getStatus();
    }

    public function __construct()
    {
    }

    public function getGenerator(): ?ConfigGenerator
    {
        return $this->generator;
    }

    public function setGenerator(?ConfigGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function getStatus(): ?ConfigDeviceStatus
    {
        return $this->status;
    }

    public function setStatus(?ConfigDeviceStatus $status)
    {
        $this->status = $status;
    }

    public function getConfigTemplate(): ?string
    {
        return $this->configTemplate;
    }

    public function setConfigTemplate(?string $configTemplate)
    {
        $this->configTemplate = $configTemplate;
    }

    public function getVariables(): ?array
    {
        return $this->variables;
    }

    public function setVariables(?array $variables)
    {
        $this->variables = $variables;
    }

    public function getConfigGenerated(): ?string
    {
        return $this->configGenerated;
    }

    public function setConfigGenerated(?string $configGenerated)
    {
        $this->configGenerated = $configGenerated;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    public function getErrorMessageTemplate(): ?string
    {
        return $this->errorMessageTemplate;
    }

    public function setErrorMessageTemplate(?string $errorMessageTemplate)
    {
        $this->errorMessageTemplate = $errorMessageTemplate;
    }

    public function getErrorMessageTemplateVariables(): ?array
    {
        return $this->errorMessageTemplateVariables;
    }

    public function setErrorMessageTemplateVariables(?array $errorMessageTemplateVariables)
    {
        $this->errorMessageTemplateVariables = $errorMessageTemplateVariables;
    }

    public function getConfig(): ?Config
    {
        return $this->config;
    }

    public function setConfig(?Config $config)
    {
        $this->config = $config;
    }
}
