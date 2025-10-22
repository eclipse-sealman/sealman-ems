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

use App\Model\AuditableInterface;
use Carve\ApiBundle\Deny\DenyInterface;
use Carve\ApiBundle\Deny\DenyTrait;
use Carve\ApiBundle\Validator\Constraints as Assert;
use Carve\ApiBundle\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[UniqueEntity('name', groups: ['accessTag:common'])]
class AccessTag implements DenyInterface, AuditableInterface
{
    use DenyTrait;

    #[Groups(['id', 'identification', AuditableInterface::GROUP])]
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[Assert\NotBlank(groups: ['accessTag:common'])]
    #[Groups(['accessTag:public', AuditableInterface::GROUP])]
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    #[ORM\ManyToMany(mappedBy: 'accessTags', targetEntity: User::class)]
    private Collection $users;

    #[ORM\ManyToMany(mappedBy: 'accessTags', targetEntity: Device::class)]
    private Collection $devices;

    #[ORM\ManyToMany(mappedBy: 'accessTags', targetEntity: DeviceTypeSecret::class)]
    private Collection $deviceTypeSecrets;

    #[ORM\ManyToMany(mappedBy: 'accessTags', targetEntity: DeviceEndpointDevice::class)]
    private Collection $endpointDevices;

    #[ORM\ManyToMany(mappedBy: 'accessTags', targetEntity: TemplateVersion::class)]
    private Collection $templateVersions;

    #[ORM\ManyToMany(mappedBy: 'accessTags', targetEntity: TemplateVersionEndpointDevice::class)]
    private Collection $templateVersionEndpointDevices;

    #[ORM\ManyToMany(mappedBy: 'accessTags', targetEntity: ConfigurationMicrosoftOidcRoleMapping::class)]
    private Collection $microsoftOidcRoleMappings;

    #[Groups(['representation', 'identification'])]
    public function getRepresentation(): string
    {
        return (string) $this->getName();
    }

    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addAccessTag($this);
        }
    }

    public function removeUser(User $user)
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeAccessTag($this);
        }
    }

    public function addDevice(Device $device)
    {
        if (!$this->devices->contains($device)) {
            $this->devices->add($device);
            $device->addAccessTag($this);
        }
    }

    public function removeDevice(Device $device)
    {
        if ($this->devices->contains($device)) {
            $this->devices->removeElement($device);
            $device->removeAccessTag($this);
        }
    }

    public function addTemplateVersion(TemplateVersion $templateVersion)
    {
        if (!$this->templateVersions->contains($templateVersion)) {
            $this->templateVersions->add($templateVersion);
            $templateVersion->addAccessTag($this);
        }
    }

    public function removeTemplateVersion(TemplateVersion $templateVersion)
    {
        if ($this->templateVersions->contains($templateVersion)) {
            $this->templateVersions->removeElement($templateVersion);
            $templateVersion->removeAccessTag($this);
        }
    }

    public function addEndpointDevice(DeviceEndpointDevice $endpointDevice)
    {
        if (!$this->endpointDevices->contains($endpointDevice)) {
            $this->endpointDevices->add($endpointDevice);
            $endpointDevice->addAccessTag($this);
        }
    }

    public function removeEndpointDevice(DeviceEndpointDevice $endpointDevice)
    {
        if ($this->endpointDevices->contains($endpointDevice)) {
            $this->endpointDevices->removeElement($endpointDevice);
            $endpointDevice->removeAccessTag($this);
        }
    }

    public function addTemplateVersionEndpointDevice(TemplateVersionEndpointDevice $templateVersionEndpointDevice)
    {
        if (!$this->templateVersionEndpointDevices->contains($templateVersionEndpointDevice)) {
            $this->templateVersionEndpointDevices->add($templateVersionEndpointDevice);
            $templateVersionEndpointDevice->addAccessTag($this);
        }
    }

    public function removeTemplateVersionEndpointDevice(TemplateVersionEndpointDevice $templateVersionEndpointDevice)
    {
        if ($this->templateVersionEndpointDevices->contains($templateVersionEndpointDevice)) {
            $this->templateVersionEndpointDevices->removeElement($templateVersionEndpointDevice);
            $templateVersionEndpointDevice->removeAccessTag($this);
        }
    }

    public function addMicrosoftOidcRoleMapping(ConfigurationMicrosoftOidcRoleMapping $microsoftOidcRoleMapping)
    {
        if (!$this->microsoftOidcRoleMappings->contains($microsoftOidcRoleMapping)) {
            $this->microsoftOidcRoleMappings->add($microsoftOidcRoleMapping);
            $microsoftOidcRoleMapping->addAccessTag($this);
        }
    }

    public function removeMicrosoftOidcRoleMapping(ConfigurationMicrosoftOidcRoleMapping $microsoftOidcRoleMapping)
    {
        if ($this->microsoftOidcRoleMappings->contains($microsoftOidcRoleMapping)) {
            $this->microsoftOidcRoleMappings->removeElement($microsoftOidcRoleMapping);
            $microsoftOidcRoleMapping->removeAccessTag($this);
        }
    }

    public function addDeviceTypeSecret(DeviceTypeSecret $deviceTypeSecret)
    {
        if (!$this->deviceTypeSecrets->contains($deviceTypeSecret)) {
            $this->deviceTypeSecrets->add($deviceTypeSecret);
            $deviceTypeSecret->addAccessTag($this);
        }
    }

    public function removeDeviceTypeSecret(DeviceTypeSecret $deviceTypeSecret)
    {
        if ($this->deviceTypeSecrets->contains($deviceTypeSecret)) {
            $this->deviceTypeSecrets->removeElement($deviceTypeSecret);
            $deviceTypeSecret->removeAccessTag($this);
        }
    }

    public function __construct()
    {
        $this->templateVersions = new ArrayCollection();
        $this->templateVersionEndpointDevices = new ArrayCollection();
        $this->endpointDevices = new ArrayCollection();
        $this->devices = new ArrayCollection();
        $this->deviceTypeSecrets = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->microsoftOidcRoleMappings = new ArrayCollection();
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

    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function setUsers(Collection $users)
    {
        $this->users = $users;
    }

    public function getDevices(): Collection
    {
        return $this->devices;
    }

    public function setDevices(Collection $devices)
    {
        $this->devices = $devices;
    }

    public function getEndpointDevices(): Collection
    {
        return $this->endpointDevices;
    }

    public function setEndpointDevices(Collection $endpointDevices)
    {
        $this->endpointDevices = $endpointDevices;
    }

    public function getTemplateVersionEndpointDevices(): Collection
    {
        return $this->templateVersionEndpointDevices;
    }

    public function setTemplateVersionEndpointDevices(Collection $templateVersionEndpointDevices)
    {
        $this->templateVersionEndpointDevices = $templateVersionEndpointDevices;
    }

    public function getTemplateVersions(): Collection
    {
        return $this->templateVersions;
    }

    public function setTemplateVersions(Collection $templateVersions)
    {
        $this->templateVersions = $templateVersions;
    }

    public function getMicrosoftOidcRoleMappings(): Collection
    {
        return $this->microsoftOidcRoleMappings;
    }

    public function setMicrosoftOidcRoleMappings(Collection $microsoftOidcRoleMappings)
    {
        $this->microsoftOidcRoleMappings = $microsoftOidcRoleMappings;
    }

    public function getDeviceTypeSecrets(): Collection
    {
        return $this->deviceTypeSecrets;
    }

    public function setDeviceTypeSecrets(Collection $deviceTypeSecrets)
    {
        $this->deviceTypeSecrets = $deviceTypeSecrets;
    }
}
