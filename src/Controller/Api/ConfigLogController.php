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
use App\Deny\ConfigLogDeny;
use App\Entity\ConfigLog;
use App\Enum\Feature;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\DeviceSecretManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Trait\ApiExportCsvTrait;
use Carve\ApiBundle\Trait\ApiExportExcelTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/configlog')]
#[Api\Resource(
    class: ConfigLog::class,
    denyClass: ConfigLogDeny::class,
    listFormFilterByAppend: ['featureName'],
    exportFormFieldAppend: ['featureName'],
)]
#[Rest\View(
    serializerGroups: [
        'identification',
        'configLog:public',
        'deviceType:identification',
        'deviceType:configFeatureName',
        'deviceType:configFeatureFormat',
        'logLevel',
        'timestampable',
        'blameable',
        'deny',
    ]
)]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
#[Areas(['admin', 'smartems'])]
class ConfigLogController extends AbstractApiController
{
    use ApiListTrait;
    use ApiExportCsvTrait;
    use ApiExportExcelTrait;
    use DeviceSecretManagerTrait;
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

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        if (!$this->isAllDevicesGranted()) {
            $this->applyUserAccessTagsQueryModification($queryBuilder, $alias);
        }
    }

    protected function modifyFilter(ListQueryFilterInterface $filter, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('featureName' === $filter->getFilterBy()) {
            $queryBuilder->leftJoin($alias.'.deviceType', 'dt1', Join::WITH, $alias.'.feature = :feature1');
            $queryBuilder->leftJoin($alias.'.deviceType', 'dt2', Join::WITH, $alias.'.feature = :feature2');
            $queryBuilder->leftJoin($alias.'.deviceType', 'dt3', Join::WITH, $alias.'.feature = :feature3');
            $queryBuilder->andWhere('dt1.nameConfig1 LIKE :featureNameValue OR dt2.nameConfig2 LIKE :featureNameValue OR dt3.nameConfig3 LIKE :featureNameValue');
            $queryBuilder->setParameter('feature1', Feature::PRIMARY);
            $queryBuilder->setParameter('feature2', Feature::SECONDARY);
            $queryBuilder->setParameter('feature3', Feature::TERTIARY);
            $queryBuilder->setParameter('featureNameValue', '%'.$filter->getFilterValue().'%');

            return true;
        }

        return false;
    }

    #[Rest\Get('/content/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get content of {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} content to return')]
    #[Api\Response200SubjectGroups('Returns content of {{ subjectLower }}')]
    #[Api\Response404Id]
    #[Rest\View(serializerGroups: ['identification', 'configLog:content', 'deny'])]
    public function getContentAction(int $id)
    {
        $object = $this->find($id, ConfigLogDeny::SHOW_CONTENT);

        $this->deviceSecretManager->createUserShowConfigLogContentLog($object);

        $this->entityManager->flush();

        // Decryption
        $object->setDecryptedContent($this->deviceSecretManager->getDecryptedConfigLogContent($object));

        return $object;
    }
}
