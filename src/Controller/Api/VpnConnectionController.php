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

namespace App\Controller\Api;

use App\Attribute\Areas;
use App\Entity\VpnConnection;
use Doctrine\ORM\QueryBuilder;
use App\Deny\VpnConnectionDeny;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use App\Trait\ApiVpnCloseConnectionTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Controller\AbstractApiController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/vpnconnection')]
#[Api\Resource(
    class: VpnConnection::class,
    denyClass: VpnConnectionDeny::class,
    listFormFilterByAppend: ['device.deviceType', 'owned']
)]
#[Rest\View(serializerGroups: ['identification', 'vpnConnection:public', 'deviceType:identification', 'timestampable', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
#[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
class VpnConnectionController extends AbstractApiController
{
    use ApiGetTrait;
    use ApiListTrait;
    use ApiVpnCloseConnectionTrait;

    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('target' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.device', $sorting->getDirection()->value);
            $queryBuilder->addOrderBy($alias.'.endpointDevice', $sorting->getDirection()->value);

            return true;
        }

        if ('connection' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.connectionStartAt', $sorting->getDirection()->value);

            return true;
        }

        return false;
    }

    protected function getSortingFieldChoices(): array
    {
        $sortingFieldChoices = parent::getSortingFieldChoices();
        $sortingFieldChoices[] = 'connection';

        return $sortingFieldChoices;
    }

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $queryBuilder->andWhere($alias.'.permanent = :permanent');
        $queryBuilder->setParameter('permanent', false);

        if (!$this->isGranted('ROLE_ADMIN_VPN')) {
            $queryBuilder->andWhere($alias.'.user = :user');
            $queryBuilder->setParameter('user', $this->getUser());
        }
    }


    protected function modifyFilter(ListQueryFilterInterface $filter, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('owned' === $filter->getFilterBy()) {
            if (!$this->isGranted('ROLE_ADMIN_VPN')) {
                // Limited on modifyQueryBuilder()
                return true;
            }

            $queryBuilder->andWhere($alias.'.user = :user');
            $queryBuilder->setParameter('user', $this->getUser());

            return true;
        }

        return false;
    }
}
