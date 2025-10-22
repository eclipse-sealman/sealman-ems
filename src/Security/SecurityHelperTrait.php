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

namespace App\Security;

use App\Entity\CommunicationLog;
use App\Entity\Config;
use App\Entity\ConfigLog;
use App\Entity\Device;
use App\Entity\DeviceEndpointDevice;
use App\Entity\DiagnoseLog;
use App\Entity\Firmware;
use App\Entity\Template;
use App\Entity\Traits\AccessTagsInterface;
use App\Entity\User;
use App\Service\Helper\AuthorizationCheckerTrait;
use App\Service\Helper\EntityManagerTrait;
use App\Service\Helper\UserTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;

trait SecurityHelperTrait
{
    use AuthorizationCheckerTrait;
    use EntityManagerTrait;
    use UserTrait;

    protected function isAllDevicesGranted(): bool
    {
        if (!$this->getUser()) {
            return false;
        }

        if (!$this->isGranted('ROLE_ADMIN') && !$this->getUser()->getRadiusUserAllDevicesAccess()) {
            return false;
        }

        return true;
    }

    protected function isAllVpnDevicesGranted(): bool
    {
        if (!$this->getUser()) {
            return false;
        }

        if (!$this->isGranted('ROLE_ADMIN_VPN') && !$this->getUser()->getRadiusUserAllDevicesAccess()) {
            return false;
        }

        return true;
    }

    // $accessTagsQueryApendix might be used to extend query
    // e.g. :accessTags MEMBER OF '.$alias.'.accessTags'." OR t.createdBy = :user OR ".$alias.".createdBy = :user
    // instead of just :accessTags MEMBER OF '.$alias.'.accessTags'
    // In this case $accessTagsQueryApendix = "OR t.createdBy = :user OR ".$alias.".createdBy = :user"
    protected function applyUserAccessTagsQueryModification(QueryBuilder $queryBuilder, string $alias, string $accessTagsQueryApendix = ''): void
    {
        if (!$this->isAllDevicesGranted()) {
            $queryBuilder->andWhere(':accessTags MEMBER OF '.$alias.'.accessTags'.' '.$accessTagsQueryApendix);
            $queryBuilder->setParameter('accessTags', $this->getUser()->getAccessTags());
        }
    }

    protected function applyUserAccessTagsQueryModificationForAccessTags(QueryBuilder $queryBuilder, string $alias): void
    {
        if (!$this->isAllDevicesGranted()) {
            $queryBuilder->andWhere(':user MEMBER OF '.$alias.'.users');
            $queryBuilder->setParameter('user', $this->getUser());
        }
    }

    protected function applyUserAccessTagsQueryModificationForTemplateComponents(QueryBuilder $queryBuilder, string $alias): void
    {
        if (!$this->isAllDevicesGranted()) {
            $user = $this->getUser();
            $accessTags = $user->getAccessTags();

            // Remove access to any template component when user has no access tags
            if (0 === count($accessTags)) {
                $queryBuilder->andWhere('1 = 0');

                return;
            }

            $queryBuilder->leftJoin($alias.'.templates1', 'tv1');
            $queryBuilder->leftJoin($alias.'.templates2', 'tv2');
            $queryBuilder->leftJoin($alias.'.templates3', 'tv3');
            $queryBuilder->leftJoin('tv1.template', 't1');
            $queryBuilder->leftJoin('tv2.template', 't2');
            $queryBuilder->leftJoin('tv3.template', 't3');
            $queryBuilder->leftJoin('t1.devices', 'd1');
            $queryBuilder->leftJoin('t2.devices', 'd2');
            $queryBuilder->leftJoin('t3.devices', 'd3');

            // Divided for better readability
            $conditions = [
                // User created config/firmware
                $alias.'.createdBy = :user',
                // Or config/firmware is used by device (via template) - device is available for user
                ':accessTags MEMBER OF d1.accessTags',
                ':accessTags MEMBER OF d2.accessTags',
                ':accessTags MEMBER OF d3.accessTags',
                // Or config/firmware is used by template (via templateVersion) - template was created by user
                't1.createdBy = :user',
                't2.createdBy = :user',
                't3.createdBy = :user',
                // Or config/firmware is used by templateVersion - templateVersion was created by user
                'tv1.createdBy = :user',
                'tv2.createdBy = :user',
                'tv3.createdBy = :user',
                // Or config/firmware is used as stagingTemplate in template with access
                'tv1.id = t1.stagingTemplate',
                'tv2.id = t2.stagingTemplate',
                'tv3.id = t3.stagingTemplate',
                // Or config/firmware is used as productionTemplate in template with access
                'tv1.id = t1.productionTemplate',
                'tv2.id = t2.productionTemplate',
                'tv3.id = t3.productionTemplate',
            ];

            $queryBuilder->andWhere(implode(' OR ', $conditions));
            $queryBuilder->setParameter('accessTags', $accessTags);
            $queryBuilder->setParameter('user', $user);
        }
    }

    protected function removeNotOwnedAccessTags(AccessTagsInterface $object): AccessTagsInterface
    {
        if (!$this->isAllDevicesGranted()) {
            $accessTags = new ArrayCollection();
            foreach ($object->getAccessTags() as $accessTag) {
                if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                    $accessTags->add($accessTag);
                }
            }
            $object->setAccessTags($accessTags);
        }

        return $object;
    }

    protected function removeNotOwnedVpnConnections(Device|DeviceEndpointDevice $object): Device|DeviceEndpointDevice
    {
        if ($object instanceof Device) {
            foreach ($object->getEndpointDevices() as $endpointDevice) {
                $this->removeNotOwnedVpnConnections($endpointDevice);
            }
        }

        $vpnConnections = new ArrayCollection();
        foreach ($object->getVpnConnections() as $vpnConnection) {
            if ($this->isGranted('ROLE_ADMIN_VPN')) {
                if (!$vpnConnection->getPermanent()) {
                    $vpnConnections->add($vpnConnection);
                }
            } else {
                if ($vpnConnection->getUser() === $this->getUser()) {
                    $vpnConnections->add($vpnConnection);
                }
            }
        }

        $object->setVpnConnections($vpnConnections);

        return $object;
    }

    protected function fillOwnedVpnConnections(Device|DeviceEndpointDevice $object): Device|DeviceEndpointDevice
    {
        if ($object instanceof Device) {
            foreach ($object->getEndpointDevices() as $endpointDevice) {
                $this->fillOwnedVpnConnections($endpointDevice);
            }
        }

        $ownedVpnConnections = new ArrayCollection();
        foreach ($object->getVpnConnections() as $vpnConnection) {
            if ($vpnConnection->getUser() === $this->getUser()) {
                $ownedVpnConnections->add($vpnConnection);
            }
        }

        $object->setOwnedVpnConnections($ownedVpnConnections);

        return $object;
    }

    protected function duplicateOwnedAccessTags(AccessTagsInterface $object, AccessTagsInterface $duplicatedObject): void
    {
        $duplicatedObject->setAccessTags(new ArrayCollection());
        foreach ($object->getAccessTags() as $accessTag) {
            if ($this->isAllDevicesGranted() || $this->getUser()->getAccessTags()->contains($accessTag)) {
                $duplicatedObject->addAccessTag($accessTag);
            }
        }
    }

    protected function isConfigAccessible(Config $config): bool
    {
        $queryBuilder = $this->getRepository(Config::class)->createQueryBuilder('c');
        $queryBuilder->andWhere('c.id = :id');
        $queryBuilder->setParameter('id', $config->getId());
        $queryBuilder->setMaxResults(1);

        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'c');

        return $queryBuilder->getQuery()->getOneOrNullResult() ? true : false;
    }

    protected function isFirmwareAccessible(Firmware $firmware): bool
    {
        $queryBuilder = $this->getRepository(Firmware::class)->createQueryBuilder('c');
        $queryBuilder->andWhere('c.id = :id');
        $queryBuilder->setParameter('id', $firmware->getId());
        $queryBuilder->setMaxResults(1);

        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'c');

        return $queryBuilder->getQuery()->getOneOrNullResult() ? true : false;
    }

    protected function isTemplateAccessible(Template $template): bool
    {
        $queryBuilder = $this->getRepository(Template::class)->createQueryBuilder('t');
        $queryBuilder->andWhere('t.id = :id');
        $queryBuilder->setParameter('id', $template->getId());

        if (!$this->isAllDevicesGranted()) {
            $queryBuilder->leftJoin('t.devices', 'd');

            $this->applyUserAccessTagsQueryModification($queryBuilder, 'd', ' OR t.createdBy = :user');

            $queryBuilder->setParameter('user', $this->getUser());
        }

        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult() ? true : false;
    }

    protected function hasAccessToLogContent(CommunicationLog|ConfigLog|DiagnoseLog $object): bool
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SMARTEMS')) {
            return false;
        }

        if ($this->isGranted('ROLE_SMARTEMS')) {
            if (!$object?->getDeviceType()) {
                return false;
            }
            $hasAccessToAllSecretsUsedAsVariables = true;

            foreach ($object->getDeviceType()->getDeviceTypeSecrets() as $deviceTypeSecret) {
                if ($deviceTypeSecret->getUseAsVariable()) {
                    $hasAccess = false;
                    foreach ($deviceTypeSecret->getAccessTags() as $accessTag) {
                        if ($this->getUser()->getAccessTags()->contains($accessTag)) {
                            $hasAccess = true;
                            break;
                        }
                    }
                    if (!$hasAccess) {
                        $hasAccessToAllSecretsUsedAsVariables = false;
                    }
                }
            }

            if (!$hasAccessToAllSecretsUsedAsVariables) {
                return false;
            }
        }

        return true;
    }

    protected function hasIntersectingAccessTag(User $user, DeviceEndpointDevice $endpointDevice): bool
    {
        foreach ($endpointDevice->getAccessTags() as $accessTag) {
            if ($user->getAccessTags()->contains($accessTag)) {
                return true;
            }
        }

        return false;
    }
}
