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

interface CommunicationEntityInterface
{
    public function getXForwardedFor(): ?string;

    public function setXForwardedFor(?string $xForwardedFor);

    public function getHost(): ?string;

    public function setHost(?string $host);

    public function getIpv6Prefix(): ?string;

    public function setIpv6Prefix(?string $ipv6Prefix);

    public function getUptime(): ?string;

    public function setUptime(?string $uptime);

    public function getUptimeSeconds(): ?int;

    public function setUptimeSeconds(?int $uptimeSeconds);

    public function getSeenAt(): ?\DateTime;

    public function setSeenAt(?\DateTime $seenAt);

    public function getRegistrationId(): ?string;

    public function setRegistrationId(?string $registrationId);

    public function getEndorsementKey(): ?string;

    public function setEndorsementKey(?string $endorsementKey);

    public function getHardwareVersion(): ?string;

    public function setHardwareVersion(?string $hardwareVersion);

    public function getSerialNumber(): ?string;

    public function setSerialNumber(?string $serialNumber);

    public function getModel(): ?string;

    public function setModel(?string $model);
}
