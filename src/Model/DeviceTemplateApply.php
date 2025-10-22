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

use App\Entity\Template;
use App\Validator\Constraints\DeviceTemplateApply as DeviceTemplateApplyValidator;

#[DeviceTemplateApplyValidator(groups: ['device:templateApply'])]
class DeviceTemplateApply
{
    /**
     * Template.
     */
    private ?Template $template = null;

    /**
     * Should device description be applied?
     */
    private ?bool $applyDeviceDescription = false;

    /**
     * Should labels be applied?
     */
    private ?bool $applyLabels = false;

    /**
     * Should endpoint devices and size of subnet be applied?
     */
    private ?bool $applyEndpointDevices = false;

    /**
     * Should variables be applied?
     */
    private ?bool $applyVariables = false;

    /**
     * Should masquerade be applied?
     */
    private ?bool $applyMasquerade = false;

    /**
     * Should access tags be applied?
     */
    private ?bool $applyAccessTags = false;

    /**
     * Should primary config be reinstalled?
     */
    private ?bool $reinstallConfig1 = false;

    /**
     * Should secondary config be reinstalled?
     */
    private ?bool $reinstallConfig2 = false;

    /**
     * Should tertiary config be reinstalled?
     */
    private ?bool $reinstallConfig3 = false;

    /**
     * Should primary firmware be reinstalled?
     */
    private ?bool $reinstallFirmware1 = false;

    /**
     * Should secondary firmware be reinstalled?
     */
    private ?bool $reinstallFirmware2 = false;

    /**
     * Should tertiary firmware be reinstalled?
     */
    private ?bool $reinstallFirmware3 = false;

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template)
    {
        $this->template = $template;
    }

    public function getApplyDeviceDescription(): ?bool
    {
        return $this->applyDeviceDescription;
    }

    public function setApplyDeviceDescription(?bool $applyDeviceDescription)
    {
        $this->applyDeviceDescription = $applyDeviceDescription;
    }

    public function getApplyEndpointDevices(): ?bool
    {
        return $this->applyEndpointDevices;
    }

    public function setApplyEndpointDevices(?bool $applyEndpointDevices)
    {
        $this->applyEndpointDevices = $applyEndpointDevices;
    }

    public function getApplyVariables(): ?bool
    {
        return $this->applyVariables;
    }

    public function setApplyVariables(?bool $applyVariables)
    {
        $this->applyVariables = $applyVariables;
    }

    public function getApplyMasquerade(): ?bool
    {
        return $this->applyMasquerade;
    }

    public function setApplyMasquerade(?bool $applyMasquerade)
    {
        $this->applyMasquerade = $applyMasquerade;
    }

    public function getApplyAccessTags(): ?bool
    {
        return $this->applyAccessTags;
    }

    public function setApplyAccessTags(?bool $applyAccessTags)
    {
        $this->applyAccessTags = $applyAccessTags;
    }

    public function getApplyLabels(): ?bool
    {
        return $this->applyLabels;
    }

    public function setApplyLabels(?bool $applyLabels)
    {
        $this->applyLabels = $applyLabels;
    }

    public function getReinstallConfig1(): ?bool
    {
        return $this->reinstallConfig1;
    }

    public function setReinstallConfig1(?bool $reinstallConfig1)
    {
        $this->reinstallConfig1 = $reinstallConfig1;
    }

    public function getReinstallConfig2(): ?bool
    {
        return $this->reinstallConfig2;
    }

    public function setReinstallConfig2(?bool $reinstallConfig2)
    {
        $this->reinstallConfig2 = $reinstallConfig2;
    }

    public function getReinstallConfig3(): ?bool
    {
        return $this->reinstallConfig3;
    }

    public function setReinstallConfig3(?bool $reinstallConfig3)
    {
        $this->reinstallConfig3 = $reinstallConfig3;
    }

    public function getReinstallFirmware1(): ?bool
    {
        return $this->reinstallFirmware1;
    }

    public function setReinstallFirmware1(?bool $reinstallFirmware1)
    {
        $this->reinstallFirmware1 = $reinstallFirmware1;
    }

    public function getReinstallFirmware2(): ?bool
    {
        return $this->reinstallFirmware2;
    }

    public function setReinstallFirmware2(?bool $reinstallFirmware2)
    {
        $this->reinstallFirmware2 = $reinstallFirmware2;
    }

    public function getReinstallFirmware3(): ?bool
    {
        return $this->reinstallFirmware3;
    }

    public function setReinstallFirmware3(?bool $reinstallFirmware3)
    {
        $this->reinstallFirmware3 = $reinstallFirmware3;
    }
}
