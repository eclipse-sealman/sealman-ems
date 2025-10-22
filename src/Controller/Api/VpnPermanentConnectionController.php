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
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/vpnpermanentconnection')]
#[Api\Resource(
    class: VpnConnection::class,
    listFormFilterByAppend: ['device.deviceType', 'sourceDeviceToNetwork', 'destinationDeviceToNetwork'],
    listFormSortingFieldAppend: ['sourceDeviceToNetwork', 'destinationDeviceToNetwork']
)]
#[Rest\View(
    serializerGroups: [
        'identification',
        'vpnConnection:public',
        'vpnConnection:deviceToNetworkPublic',
        'deviceType:identification',
        'timestampable',
        'blameable',
        'deny',
    ]
)]
#[Security("is_granted('ROLE_ADMIN_VPN')")]
#[Areas(['admin:vpnsecuritysuite'])]
class VpnPermanentConnectionController extends AbstractApiController
{
    use ApiGetTrait;
    use ApiListTrait;

    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        // TODO Arek This should be adjusted on frontend side. There is no additional logic here, just renaming field sorts
        if ('target' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.device', $sorting->getDirection()->value);

            return true;
        }

        if ('connection' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.connectionStartAt', $sorting->getDirection()->value);

            return true;
        }

        if ('sourceDeviceToNetwork' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.source', $sorting->getDirection()->value);

            return true;
        }

        if ('destinationDeviceToNetwork' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.destination', $sorting->getDirection()->value);

            return true;
        }

        return false;
    }

    protected function modifyFilter(ListQueryFilterInterface $filter, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('sourceDeviceToNetwork' === $filter->getFilterBy()) {
            $queryBuilder->andWhere($alias.'.source LIKE :source');
            $queryBuilder->setParameter('source', '%'.$filter->getFilterValue().'%');

            return true;
        }

        if ('destinationDeviceToNetwork' === $filter->getFilterBy()) {
            $queryBuilder->andWhere($alias.'.destination LIKE :destination');
            $queryBuilder->setParameter('destination', '%'.$filter->getFilterValue().'%');

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
        $queryBuilder->setParameter('permanent', true);
    }
}
