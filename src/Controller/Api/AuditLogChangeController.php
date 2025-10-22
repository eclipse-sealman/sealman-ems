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
use App\Entity\AuditLogChange;
use App\Security\SecurityHelperTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/auditlogchange')]
#[Api\Resource(
    class: AuditLogChange::class,
    listFormFilterByAppend: ['log.id'],
)]
#[Rest\View(
    serializerGroups: [
        'identification',
        'auditLogChange:public',
        'timestampable',
        'blameable',
        'deny',
    ]
)]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class AuditLogChangeController extends AbstractApiController
{
    use ApiListTrait;
    use SecurityHelperTrait;

    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('createdAt' === $sorting->getField()) {
            $queryBuilder->addOrderBy($alias.'.'.$sorting->getField(), $sorting->getDirection()->value);
            $queryBuilder->addOrderBy($alias.'.id', $sorting->getDirection()->value);

            return true;
        }

        return false;
    }

    #[Rest\Get('/values/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get values of {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} values to return')]
    #[Api\Response200SubjectGroups('Returns values of {{ subjectLower }}')]
    #[Api\Response404Id]
    #[Rest\View(serializerGroups: ['identification', 'auditLogChangeValues:public', 'deny'])]
    public function getValuesAction(int $id)
    {
        $object = $this->find($id);

        return $object?->getAuditLogChangeValues();
    }
}
