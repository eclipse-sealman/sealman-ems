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

use App\Validator\Constraints\RouterCommunicationIdentifier;
use Carve\ApiBundle\Validator\Constraints as Assert;

/**
 * Field names must be exactly as in incoming request from router (case sensitive!).
 *
 * Router communication procedure it works for TK800, TK600, TK500, TK500v2, TK100
 */
#[RouterCommunicationIdentifier]
class RouterModel
{
    public const VARIABLE_NAME_CERTIFICATE = 'certificate';
    public const VARIABLE_NAME_PRIVATE_KEY = 'privateKey';
    public const VARIABLE_NAME_CA = 'ca';
    public const VARIABLE_NAME_ROOT_CA = 'rootCa';
    public const VARIABLE_NAME_CERTIFICATE_PLAIN = 'certificatePlain';
    public const VARIABLE_NAME_PRIVATE_KEY_PLAIN = 'privateKeyPlain';
    public const VARIABLE_NAME_CA_PLAIN = 'caPlain';
    public const VARIABLE_NAME_ROOT_CA_PLAIN = 'rootCaPlain';
    public const VARIABLE_NAME_CERTIFICATE_CHECKSUM = 'certificateChecksum';
    public const VARIABLE_NAME_PRIVATE_KEY_CHECKSUM = 'privateKeyChecksum';
    public const VARIABLE_NAME_CA_CHECKSUM = 'caChecksum';
    public const VARIABLE_NAME_ROOT_CA_CHECKSUM = 'rootCaChecksum';
    public const VARIABLE_NAME_PRIVATE_KEY_PASSWORD = 'privateKeyPassword';
    public const VARIABLE_NAME_SERIAL = 'SerialNr';
    public const VARIABLE_NAME_IMSI = 'IMSI';
    public const VARIABLE_NAME_SOURCEIP = 'SourceIP';
    public const VARIABLE_NAME_XFORWARDEDFORIP = 'XForwardedForIP';
    public const VARIABLE_NAME_VIP_SUBNET = 'vip_subnet';
    public const VARIABLE_NAME_VIP_PREFIX = 'vip_';
    public const VARIABLE_NAME_PIP_PREFIX = 'pip_';
    public const VARIABLE_NAME_VIP_ARRAY = 'vips';
    public const VARIABLE_NAME_PIP_ARRAY = 'pips';

    /**
     * Serial number.
     */
    #[Assert\Length(max: 255, groups: ['Default', 'authentication'])]
    private ?string $Serial = null;

    /**
     * Firmware version.
     */
    #[Assert\NotBlank(groups: ['router'])]
    private ?string $Firmware = null;

    /**
     * Device supervisor agent package version.
     */
    #[Assert\NotBlank(groups: ['deviceSupervisorAgent'])]
    #[Assert\Length(max: 255)]
    private ?string $agentVersion = null;

    /**
     * Device supervisor pySdkPackage version.
     */
    #[Assert\NotBlank(groups: ['deviceSupervisorAgent'])]
    #[Assert\Length(max: 255)]
    private ?string $pySdkPackageVersion = null;

    /**
     * Model.
     */
    #[Assert\NotBlank(groups: ['fieldModelRequired'])]
    #[Assert\Length(max: 255)]
    private ?string $Model = null;

    /**
     * Cell ID.
     */
    #[Assert\Length(max: 255)]
    private ?string $CellID = null;

    /**
     * RSRP.
     */
    // Assert\Type is needed here, but due to way RouterXType works it does not emit message set below
    #[Assert\IntegerType(type: 'integer')]
    #[Assert\GreaterThanOrEqual(value: -2147483647, groups: ['router'])]
    #[Assert\LessThanOrEqual(value: 2147483647, groups: ['router'])]
    private ?int $RSRP = null;

    /**
     * IMEI.
     */
    #[Assert\Length(max: 255)]
    private ?string $IMEI = null;

    /**
     * IMSI.
     */
    #[Assert\Length(max: 255, groups: ['Default', 'authentication'])]
    private ?string $IMSI = null;

    /**
     * Router uptime.
     */
    #[Assert\Length(max: 255)]
    private ?string $RouterUptime = null;

    /**
     * Operator code.
     */
    #[Assert\Length(max: 255)]
    private ?string $OperatorCode = null;

    /**
     * Band.
     */
    #[Assert\Length(max: 255)]
    private ?string $Band = null;

    /**
     * Cellular1 IP.
     */
    #[Assert\Length(max: 255)]
    private ?string $Cellular1_IP = null;

    /**
     * Cellular1 uptime.
     */
    #[Assert\Length(max: 255)]
    private ?string $Cellular1_uptime = null;

    /**
     * Cellular2 IP.
     */
    #[Assert\Length(max: 255)]
    private ?string $Cellular2_IP = null;

    /**
     * Cellular2 uptime.
     */
    #[Assert\Length(max: 255)]
    private ?string $Cellular2_uptime = null;

    /**
     * IPv6Prefix.
     */
    #[Assert\Length(max: 255)]
    private ?string $IPv6Prefix = null;

    /**
     * Configuration.
     */
    private ?string $config = null;

    public function getSerial(): ?string
    {
        return $this->Serial;
    }

    public function setSerial(?string $Serial)
    {
        $this->Serial = $Serial;
    }

    public function getFirmware(): ?string
    {
        return $this->Firmware;
    }

    public function setFirmware(?string $Firmware)
    {
        $this->Firmware = $Firmware;
    }

    public function getAgentVersion(): ?string
    {
        return $this->agentVersion;
    }

    public function setAgentVersion(?string $agentVersion)
    {
        $this->agentVersion = $agentVersion;
    }

    public function getPySdkPackageVersion(): ?string
    {
        return $this->pySdkPackageVersion;
    }

    public function setPySdkPackageVersion(?string $pySdkPackageVersion)
    {
        $this->pySdkPackageVersion = $pySdkPackageVersion;
    }

    public function getModel(): ?string
    {
        return $this->Model;
    }

    public function setModel(?string $Model)
    {
        $this->Model = $Model;
    }

    public function getCellID(): ?string
    {
        return $this->CellID;
    }

    public function setCellID(?string $CellID)
    {
        $this->CellID = $CellID;
    }

    public function getRSRP(): ?int
    {
        return $this->RSRP;
    }

    public function setRSRP(?int $RSRP)
    {
        $this->RSRP = $RSRP;
    }

    public function getIMEI(): ?string
    {
        return $this->IMEI;
    }

    public function setIMEI(?string $IMEI)
    {
        $this->IMEI = $IMEI;
    }

    public function getIMSI(): ?string
    {
        return $this->IMSI;
    }

    public function setIMSI(?string $IMSI)
    {
        $this->IMSI = $IMSI;
    }

    public function getRouterUptime(): ?string
    {
        return $this->RouterUptime;
    }

    public function setRouterUptime(?string $RouterUptime)
    {
        $this->RouterUptime = $RouterUptime;
    }

    public function getOperatorCode(): ?string
    {
        return $this->OperatorCode;
    }

    public function setOperatorCode(?string $OperatorCode)
    {
        $this->OperatorCode = $OperatorCode;
    }

    public function getBand(): ?string
    {
        return $this->Band;
    }

    public function setBand(?string $Band)
    {
        $this->Band = $Band;
    }

    public function getCellular1IP(): ?string
    {
        return $this->Cellular1_IP;
    }

    public function setCellular1IP(?string $Cellular1_IP)
    {
        $this->Cellular1_IP = $Cellular1_IP;
    }

    public function getCellular1Uptime(): ?string
    {
        return $this->Cellular1_uptime;
    }

    public function setCellular1Uptime(?string $Cellular1_uptime)
    {
        $this->Cellular1_uptime = $Cellular1_uptime;
    }

    public function getCellular2IP(): ?string
    {
        return $this->Cellular2_IP;
    }

    public function setCellular2IP(?string $Cellular2_IP)
    {
        $this->Cellular2_IP = $Cellular2_IP;
    }

    public function getCellular2Uptime(): ?string
    {
        return $this->Cellular2_uptime;
    }

    public function setCellular2Uptime(?string $Cellular2_uptime)
    {
        $this->Cellular2_uptime = $Cellular2_uptime;
    }

    public function getIPv6Prefix(): ?string
    {
        return $this->IPv6Prefix;
    }

    public function setIPv6Prefix(?string $IPv6Prefix)
    {
        $this->IPv6Prefix = $IPv6Prefix;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function setConfig(?string $config)
    {
        $this->config = $config;
    }
}
