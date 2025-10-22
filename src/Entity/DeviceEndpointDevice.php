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

namespace App\Entity;

use App\Entity\Traits\AccessTagsInterface;
use App\Entity\Traits\BlameableEntityInterface;
use App\Entity\Traits\BlameableEntityTrait;
use App\Entity\Traits\InjectedAccessTagsInterface;
use App\Entity\Traits\InjectedAccessTagsTrait;
use App\Entity\Traits\TimestampableEntityInterface;
use App\Entity\Traits\TimestampableEntityTrait;
use App\Entity\Traits\VpnDeviceEntityTrait;
use App\Entity\Traits\VpnEndpointDeviceEntityTrait;
use App\Model\AuditableInterface;
use App\Model\DeviceEndpointDeviceLock;
use App\Validator\Constraints\EndpointDevice as EndpointDeviceValidator;
use App\Validator\Constraints\EndpointDeviceLock as EndpointDeviceLockValidator;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[EndpointDeviceValidator(groups: ['device:common', 'deviceEndpointDevice:common'])]
#[EndpointDeviceLockValidator(groups: ['deviceEndpointDevice:lock'])]
class DeviceEndpointDevice implements DenyInterface, TimestampableEntityInterface, BlameableEntityInterface, AuditableInterface, AccessTagsInterface, InjectedAccessTagsInterface
{
    use DenyTrait;
    use VpnEndpointDeviceEntityTrait;
    use VpnDeviceEntityTrait;
    use TimestampableEntityTrait;
    use BlameableEntityTrait;
    use InjectedAccessTagsTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Name.
     */
    #[Groups(['deviceEndpointDevice:public', 'device:public', AuditableInterface::GROUP])]
    #[Assert\NotBlank(groups: ['device:common', 'deviceEndpointDevice:common'])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * Description.
     */
    #[Groups(['deviceEndpointDevice:public', 'device:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Device.
     */
    #[Groups(['deviceEndpointDevice:public', 'deviceType:identification', AuditableInterface::GROUP])]
    #[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'endpointDevices')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Device $device = null;

    /**
     * Access tags.
     */
    #[Groups(['deviceEndpointDevice:admin', 'device:adminVpn', 'device:vpnEndpointDevices', 'deviceEndpointDevice:vpnEndpointDevices', AuditableInterface::GROUP])]
    #[ORM\ManyToMany(inversedBy: 'endpointDevices', targetEntity: AccessTag::class)]
    private Collection $accessTags;

    /**
     * VPN connections.
     */
    #[Groups(['device:public', 'deviceEndpointDevice:public'])]
    #[ORM\OneToMany(mappedBy: 'endpointDevice', targetEntity: VpnConnection::class)]
    private Collection $vpnConnections;

    /**
     * VPN logs.
     */
    #[Groups(['deviceEndpointDevice:public'])]
    #[ORM\OneToMany(mappedBy: 'endpointDevice', targetEntity: VpnLog::class)]
    private Collection $vpnLogs;

    /**
     * Owned VPN connections.
     */
    #[Groups(['device:public', 'deviceEndpointDevice:public'])]
    #[OA\Property(type: 'array', items: new OA\Items(ref: new NA\Model(type: VpnConnection::class)))]
    private Collection $ownedVpnConnections;

    /**
     * Helper field used for validation. Read more in DeviceEndpointDeviceLock.
     */
    private ?DeviceEndpointDeviceLock $lock = null;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    #[Groups(['deviceEndpointDevice:public'])]
    public function getVirtualSubnet(): ?string
    {
        return $this->getDevice() ? $this->getDevice()->getVirtualSubnet() : null;
    }

    public function getVirtualSubnetIp(): ?string
    {
        return $this->getDevice() ? $this->getDevice()->getVirtualSubnetIp() : null;
    }

    public function getCertificateSubject(): ?string
    {
        return $this->getDevice() ? $this->getDevice()->getCertificateSubject() : null;
    }

    public function addAccessTag(AccessTag $accessTag)
    {
        if (!$this->accessTags->contains($accessTag)) {
            $this->accessTags->add($accessTag);
            $accessTag->addEndpointDevice($this);
        }
    }

    public function removeAccessTag(AccessTag $accessTag)
    {
        if ($this->accessTags->contains($accessTag)) {
            $this->accessTags->removeElement($accessTag);
            $accessTag->removeEndpointDevice($this);
        }
    }

    public function __construct()
    {
        $this->accessTags = new ArrayCollection();
        $this->vpnLogs = new ArrayCollection();
        $this->vpnConnections = new ArrayCollection();
        $this->ownedVpnConnections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description;
    }

    public function getDevice(): ?Device
    {
        return $this->device;
    }

    public function setDevice(?Device $device)
    {
        $this->device = $device;
    }

    public function getAccessTags(): Collection
    {
        return $this->accessTags;
    }

    public function setAccessTags(Collection $accessTags)
    {
        $this->accessTags = $accessTags;
    }

    public function getVpnLogs(): Collection
    {
        return $this->vpnLogs;
    }

    public function setVpnLogs(Collection $vpnLogs)
    {
        $this->vpnLogs = $vpnLogs;
    }

    public function getVpnConnections(): Collection
    {
        return $this->vpnConnections;
    }

    public function setVpnConnections(Collection $vpnConnections)
    {
        $this->vpnConnections = $vpnConnections;
    }

    public function getOwnedVpnConnections(): Collection
    {
        return $this->ownedVpnConnections;
    }

    public function setOwnedVpnConnections(Collection $ownedVpnConnections)
    {
        $this->ownedVpnConnections = $ownedVpnConnections;
    }

    public function getLock(): ?DeviceEndpointDeviceLock
    {
        return $this->lock;
    }

    public function setLock(?DeviceEndpointDeviceLock $lock)
    {
        $this->lock = $lock;
    }
}
