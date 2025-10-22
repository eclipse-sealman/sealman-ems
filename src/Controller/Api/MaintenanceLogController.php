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
use App\Entity\MaintenanceLog;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/maintenancelog')]
#[Api\Resource(
    class: MaintenanceLog::class,
    listFormFilterByAppend: ['maintenance.id'],
    listFormSortingFieldAppend: ['maintenanceId']
)]
#[Rest\View(serializerGroups: ['identification', 'maintenanceLog:public', 'logLevel', 'createdAt', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class MaintenanceLogController extends AbstractApiController
{
    use ApiListTrait;

    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('maintenanceId' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.id', $sorting->getDirection()->value);

            return true;
        }

        return false;
    }
}
