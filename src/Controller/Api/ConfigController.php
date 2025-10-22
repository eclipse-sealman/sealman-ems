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
use App\Deny\ConfigDeny;
use App\Entity\Config;
use App\Entity\Device;
use App\Enum\Feature;
use App\Form\ConfigCreateType;
use App\Form\ConfigEditType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuditableManagerTrait;
use App\Service\Helper\UploadManagerTrait;
use App\Trait\ApiDuplicateTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiExportCsvTrait;
use Carve\ApiBundle\Trait\ApiExportExcelTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Uuid;

#[Rest\Route('/config')]
#[Api\Resource(
    class: Config::class,
    createFormClass: ConfigCreateType::class,
    editFormClass: ConfigEditType::class,
    denyClass: ConfigDeny::class,
    listFormFilterByAppend: ['featureName']
)]
#[Rest\View(serializerGroups: ['identification', 'config:public', 'timestampable', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
#[Areas(['admin', 'smartems'])]
class ConfigController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiEditTrait;
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiDuplicateTrait;
    use ApiListTrait;
    use ApiExportCsvTrait;
    use ApiExportExcelTrait;
    use UploadManagerTrait;
    use SecurityHelperTrait;
    use AuditableManagerTrait;

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, $alias);
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

    protected function processCreate($object, FormInterface $form)
    {
        $object->setUuid($this->getUniqueUuid());

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }

    protected function processEdit($object, FormInterface $form)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        if ($object->getReinstallConfig()) {
            $configField = 'config'.$object->getFeature()->value.'_id';
            $configFieldOrm = 'config'.$object->getFeature()->value;
            $reinstallField = 'reinstall_config'.$object->getFeature()->value;
            $reinstallFieldOrm = 'reinstallConfig'.$object->getFeature()->value;

            // Same logic is used in modifyFilter in DeviceController to filter connected devices by config
            // Doctrine is not supporting joins in update queries
            $sql = '
            UPDATE device d
            LEFT JOIN template t ON t.id = d.template_id
            LEFT JOIN template_version tvp ON tvp.id = t.production_template_id
            LEFT JOIN template_version tvs ON tvs.id = t.staging_template_id
            SET d.'.$reinstallField.' = true
            WHERE (d.staging = true AND tvs.'.$configField.' = :configId) OR (d.staging = false AND tvp.'.$configField.' = :configId)
            ';

            // Prepare SELECT query with $queryBuilder which is identical to UPDATE query above. It will be used to prepare partial batch update
            $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder('d');
            $queryBuilder->leftJoin('d.template', 't');
            $queryBuilder->leftJoin('t.productionTemplate', 'tvp');
            $queryBuilder->leftJoin('t.stagingTemplate', 'tvs');
            $queryBuilder->andWhere('d.'.$reinstallFieldOrm.' = :reinstall');
            $queryBuilder->setParameter('reinstall', false);
            $queryBuilder->andWhere('(d.staging = :stagingTrue AND tvs.'.$configFieldOrm.' = :config) OR (d.staging = :stagingFalse AND tvp.'.$configFieldOrm.' = :config)');
            $queryBuilder->setParameter('stagingTrue', true);
            $queryBuilder->setParameter('stagingFalse', false);
            $queryBuilder->setParameter('config', $object);

            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $this->auditableManager->createPartialBatchUpdate($queryBuilder, [$reinstallFieldOrm => false], [$reinstallFieldOrm => true]);

                $statement = $connection->prepare($sql);
                $statement->bindValue('configId', $object->getId());
                $statement->execute();

                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }

        return $object;
    }

    protected function processDuplicate($object)
    {
        $duplicatedObject = $this->getDuplicatedObject($object);
        $duplicatedObject->setUuid($this->getUniqueUuid());
        $duplicatedObject->setName($this->getUniqueCopiedString($object, 'name'));

        $this->entityManager->persist($duplicatedObject);
        $this->entityManager->flush();

        return $duplicatedObject;
    }

    protected function getUniqueUuid(): string
    {
        $uuid4 = Uuid::v4()->toRfc4122();
        $count = $this->getRepository(Config::class)->count(['uuid' => $uuid4]);

        return $count > 0 ? $this->getUniqueUuid() : $uuid4;
    }
}
