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
use App\Entity\DeviceCommand;
use App\Security\SecurityHelperTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Trait\ApiExportCsvTrait;
use Carve\ApiBundle\Trait\ApiExportExcelTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/devicecommand')]
#[Api\Resource(
    class: DeviceCommand::class,
    listFormFilterByAppend: ['device.deviceType'],
    exportFormFieldAppend: ['deviceType']
)]
#[Rest\View(serializerGroups: ['identification', 'deviceCommand:public', 'deviceType:identification',  'timestampable', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
#[Areas(['admin', 'smartems'])]
class DeviceCommandController extends AbstractApiController
{
    use ApiListTrait;
    use ApiExportCsvTrait;
    use ApiExportExcelTrait;
    use SecurityHelperTrait;

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $queryBuilder->leftJoin($alias.'.device', 'd');
        $this->applyUserAccessTagsQueryModification($queryBuilder, 'd');
    }

    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('createdAt' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.'.$sorting->getField(), $sorting->getDirection()->value);
            $queryBuilder->addOrderBy($alias.'.id', $sorting->getDirection()->value);

            return true;
        }

        return false;
    }
}
