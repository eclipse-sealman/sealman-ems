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
use App\Entity\VpnLog;
use App\Security\SecurityHelperTrait;
use Carve\ApiBundle\Attribute\AddRoleBasedSerializerGroups;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Trait\ApiExportCsvTrait;
use Carve\ApiBundle\Trait\ApiExportExcelTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/vpnlog')]
#[Api\Resource(
    class: VpnLog::class,
    listFormFilterByAppend: ['deviceType'],
    exportFormFieldAppend: ['deviceType']
)]
#[Rest\View(serializerGroups: ['identification', 'vpnLog:public', 'deviceType:identification', 'logLevel', 'createdAt', 'blameable', 'deny'])]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN_SCEP', ['vpnLog:admin'])]
#[Security("is_granted('ROLE_ADMIN_SCEP') or is_granted('ROLE_VPN')")]
#[Areas(['admin:scep', 'vpnsecuritysuite'])]
class VpnLogController extends AbstractApiController
{
    use ApiListTrait;
    use ApiExportCsvTrait;
    use ApiExportExcelTrait;
    use SecurityHelperTrait;

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $queryBuilder->leftJoin($alias.'.device', 'd');
        $queryBuilder->leftJoin($alias.'.endpointDevice', 'ed');
        $queryBuilder->leftJoin('d.endpointDevices', 'ded');

        $this->applyUserAccessTagsQueryModification($queryBuilder, 'd', 'OR :accessTags MEMBER OF ed.accessTags OR :accessTags MEMBER OF ded.accessTags');
    }

    protected function modifyFilter(ListQueryFilterInterface $filter, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('deviceType' === $filter->getFilterBy()) {
            // d and ed available from modifyQueryBuilder
            $queryBuilder->leftJoin('ed.device', 'edd');
            $queryBuilder->andWhere('d.deviceType IN (:deviceType) OR edd.deviceType IN (:deviceType)');
            $queryBuilder->setParameter('deviceType', $filter->getFilterValue());

            return true;
        }

        return false;
    }

    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('createdAt' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.'.$sorting->getField(), $sorting->getDirection()->value);
            $queryBuilder->addOrderBy($alias.'.id', $sorting->getDirection()->value);

            return true;
        }

        if ('target' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.device', $sorting->getDirection()->value);
            $queryBuilder->addOrderBy($alias.'.endpointDevice', $sorting->getDirection()->value);
            $queryBuilder->addOrderBy($alias.'.user', $sorting->getDirection()->value);

            return true;
        }

        return false;
    }
}
