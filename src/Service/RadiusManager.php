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

namespace App\Service;

use App\Entity\AccessTag;
use App\Entity\User;
use App\Enum\RadiusAuthenticationProtocol;
use App\Enum\RadiusUserRole;
use App\Security\Radius;
use App\Service\Helper\ActorProviderTrait;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EntityManagerTrait;

class RadiusManager
{
    use ConfigurationManagerTrait;
    use EntityManagerTrait;
    use ActorProviderTrait;

    /**
     * @var Radius class
     */
    protected $radius;

    protected function getRadius()
    {
        return $this->radius;
    }

    protected function setupRadius(): bool
    {
        if (!$this->getConfiguration()->getRadiusServer() ||
            !$this->getConfiguration()->getRadiusSecret() ||
            !$this->getConfiguration()->getRadiusNasAddress() ||
            !$this->getConfiguration()->getRadiusNasPort()) {
            return false;
        }

        $this->radius = new Radius();

        $this->radius->setServer($this->getConfiguration()->getRadiusServer());
        $this->radius->setSecret($this->getConfiguration()->getRadiusSecret());
        $this->radius->setNasIpAddress($this->getConfiguration()->getRadiusNasAddress());
        $this->radius->setNasPort($this->getConfiguration()->getRadiusNasPort());

        return true;
    }

    public function checkCredentials(string $username, string $password): bool
    {
        if (!$this->getConfiguration()->getRadiusEnabled()) {
            return false;
        }

        if (!$this->getRadius()) {
            if (!$this->setupRadius()) {
                return false;
            }
        }

        if (RadiusAuthenticationProtocol::CHAP == $this->getConfiguration()->getRadiusAuth()) {
            $this->getRadius()->setChapPassword($password);

            return $this->getRadius()->accessRequest($username);
        }

        if (RadiusAuthenticationProtocol::PAP == $this->getConfiguration()->getRadiusAuth()) {
            return $this->getRadius()->accessRequest($username, $password);
        }
    }

    public function prepareUser(string $username): ?User
    {
        if (!$this->getConfiguration()->getRadiusEnabled()) {
            return null;
        }

        $this->actorProvider->setSystemActor();

        $user = new User();
        $user->setUsername($username);
        $user->setRadiusUser(true);
        $user->setEnabled(true);
        $user->setPassword('RADIUS');
        $user->setSalt('RADIUS');

        return $user;
    }

    public function applyMapping(User $user): bool
    {
        $valid = true;

        if (!$this->applyWelotecGroupMapping($user)) {
            $valid = false;
        }

        $this->applyWelotecTagMapping($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $valid;
    }

    protected function applyWelotecGroupMapping(User $user): bool
    {
        $user->setRoleAdmin(false);
        $user->setRoleSmartems(false);
        $user->setRoleVpn(false);
        $user->setRoleVpnEndpointDevices(false);
        $user->setRadiusUserAllDevicesAccess(false);

        if (!$this->getConfiguration()->getRadiusWelotecGroupMappingEnabled()) {
            $user->setRoleAdmin(true);
        } else {
            $groupName = $this->getRadius()->getAttributeVendorSpecificByName('Welotec-Group-Name');
            $mappings = $this->getConfiguration()->getRadiusWelotecGroupMappings();
            foreach ($mappings as $mapping) {
                if ($mapping->getName() == $groupName) {
                    switch ($mapping->getRadiusUserRole()) {
                        case RadiusUserRole::ADMIN:
                            $user->setRoleAdmin(true);
                            break;
                        case RadiusUserRole::SMARTEMS:
                            $user->setRoleSmartems(true);
                            break;
                            // License is not checked, because in later stages, license limits will be applied
                        case RadiusUserRole::VPN:
                            $user->setRoleVpn(true);

                            if ($mapping->getRoleVpnEndpointDevices()) {
                                $user->setRoleVpnEndpointDevices(true);
                            }
                            break;
                        case RadiusUserRole::SMARTEMS_VPN:
                            $user->setRoleVpn(true);
                            $user->setRoleSmartems(true);

                            if ($mapping->getRoleVpnEndpointDevices()) {
                                $user->setRoleVpnEndpointDevices(true);
                            }
                            break;
                    }

                    return true;
                }
            }

            return false;
        }

        return true;
    }

    protected function applyWelotecTagMapping(User $user): void
    {
        if (!$this->isWelotecTagMappingValid($user)) {
            $user->getAccessTags()->clear();

            return;
        }

        if ($this->getConfiguration()->getRadiusWelotecGroupMappingEnabled() &&
            !$this->getConfiguration()->getRadiusWelotecTagMappingEnabled() &&
            !$user->getRoleAdmin()) {
            $user->setRadiusUserAllDevicesAccess(true);
            $user->getAccessTags()->clear();
        } else {
            $tagName = $this->getRadius()->getAttributeVendorSpecificByName('Welotec-Tag-Name');

            $accessTagNames = explode(',', $tagName);
            $accessTagNames = array_map('trim', $accessTagNames);

            $accessTags = $this->getRepository(AccessTag::class)->findBy(['name' => $accessTagNames]);

            $user->getAccessTags()->clear();

            foreach ($accessTags as $accessTag) {
                $user->getAccessTags()->add($accessTag);
            }
        }
    }

    public function isWelotecTagMappingValid(User $user): bool
    {
        if ($this->getConfiguration()->getRadiusWelotecGroupMappingEnabled() &&
            $this->getConfiguration()->getRadiusWelotecTagMappingEnabled() &&
            !$user->getRoleAdmin()) {
            $tagName = $this->getRadius()->getAttributeVendorSpecificByName('Welotec-Tag-Name');

            if (!$tagName) {
                return false;
            }
        }

        return true;
    }
}
