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

namespace App\Entity\Traits;

interface GsmEntityInterface
{
    public function getImei(): ?string;

    public function setImei(?string $imei);

    public function getImsi(): ?string;

    public function setImsi(?string $imsi);

    public function getImsi2(): ?string;

    public function setImsi2(?string $imsi2);

    public function getOperatorCode(): ?string;

    public function setOperatorCode(?string $operatorCode);

    public function getBand(): ?string;

    public function setBand(?string $band);

    public function getCellId(): ?string;

    public function setCellId(?string $cellId);

    public function getRsrp(): ?string;

    public function setRsrp(?string $rsrp);

    public function getRsrpValue(): ?int;

    public function setRsrpValue(?int $rsrpValue);

    public function getCellularIp1(): ?string;

    public function setCellularIp1(?string $cellularIp1);

    public function getCellularUptime1(): ?string;

    public function setCellularUptime1(?string $cellularUptime1);

    public function getCellularUptimeSeconds1(): ?int;

    public function setCellularUptimeSeconds1(?int $cellularUptimeSeconds1);

    public function getCellularIp2(): ?string;

    public function setCellularIp2(?string $cellularIp2);

    public function getCellularUptime2(): ?string;

    public function setCellularUptime2(?string $cellularUptime2);

    public function getCellularUptimeSeconds2(): ?int;

    public function setCellularUptimeSeconds2(?int $cellularUptimeSeconds2);
}
