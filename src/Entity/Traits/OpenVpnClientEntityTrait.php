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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait OpenVpnClientEntityTrait
{
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cscCertificateSubject = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cscVpnIp = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cscVirtualSubnet = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $cscHash = null;

    public function getCscCertificateSubject(): ?string
    {
        return $this->cscCertificateSubject;
    }

    public function setCscCertificateSubject(?string $cscCertificateSubject)
    {
        $this->cscCertificateSubject = $cscCertificateSubject;
    }

    public function getCscVpnIp(): ?string
    {
        return $this->cscVpnIp;
    }

    public function setCscVpnIp(?string $cscVpnIp)
    {
        $this->cscVpnIp = $cscVpnIp;
    }

    public function getCscVirtualSubnet(): ?string
    {
        return $this->cscVirtualSubnet;
    }

    public function setCscVirtualSubnet(?string $cscVirtualSubnet)
    {
        $this->cscVirtualSubnet = $cscVirtualSubnet;
    }

    public function getCscHash(): ?string
    {
        return $this->cscHash;
    }

    public function setCscHash(?string $cscHash)
    {
        $this->cscHash = $cscHash;
    }
}
