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

use App\Model\AuditableInterface;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait VpnEndpointDeviceEntityTrait
{
    #[Groups(['device:vpnDevicePublic', 'deviceEndpointDevice:public', 'templateVersion:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['templateVersion:common', 'device:common', 'deviceEndpointDevice:common'])]
    #[Assert\Ip(groups: ['templateVersion:common', 'device:common', 'deviceEndpointDevice:common'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $physicalIp = null;

    #[Groups(['device:vpnDevicePublic', 'deviceEndpointDevice:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $physicalIpSortable = null;

    #[Groups(['device:vpnDevicePublic', 'deviceEndpointDevice:public', 'templateVersion:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['templateVersion:common', 'device:common', 'deviceEndpointDevice:common'])]
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $virtualIpHostPart = null;

    public function getPhysicalIp(): ?string
    {
        return $this->physicalIp;
    }

    public function setPhysicalIp(?string $physicalIp)
    {
        $this->physicalIp = $physicalIp;

        /**
         * Fill $physicalIpSortable manually.
         *
         * Doctrine lifecycles (prePersist/preUpdate) have following drawbacks:
         * 1. They do not trigger update on $physicalIpSortable field which is needed by AuditLog.
         *    This potentially could be done by manually triggering changeSet computation, but it makes this too complex for such a simple use case.
         * 2. $physicalIpSortable is updated instantly after change (without the need to actually flush it with doctrine) and
         *    can be used for further calculations.
         */
        $physicalIpSortable = $physicalIp ? \ip2long($physicalIp) : false;
        $this->setPhysicalIpSortable(false !== $physicalIpSortable ? $physicalIpSortable : null);
    }

    public function getPhysicalIpSortable(): ?int
    {
        return $this->physicalIpSortable;
    }

    public function setPhysicalIpSortable(?int $physicalIpSortable)
    {
        $this->physicalIpSortable = $physicalIpSortable;
    }

    public function getVirtualIpHostPart(): ?int
    {
        return $this->virtualIpHostPart;
    }

    public function setVirtualIpHostPart(?int $virtualIpHostPart)
    {
        $this->virtualIpHostPart = $virtualIpHostPart;
    }
}
