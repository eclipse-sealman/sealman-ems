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
use App\Deny\FirmwareDeny;
use App\Entity\Firmware;
use App\Enum\Feature;
use App\Enum\SourceType;
use App\Form\FirmwareCreateType;
use App\Form\FirmwareEditExternalUrlType;
use App\Form\FirmwareEditUploadType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\UploadManagerTrait;
use App\Trait\ApiDuplicateTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiExportCsvTrait;
use Carve\ApiBundle\Trait\ApiExportExcelTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

#[Rest\Route('/firmware')]
#[Api\Resource(
    class: Firmware::class,
    createFormClass: FirmwareCreateType::class,
    denyClass: FirmwareDeny::class,
    listFormFilterByAppend: ['featureName']
)]
#[Rest\View(serializerGroups: ['identification', 'firmware:public', 'timestampable', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
#[Areas(['admin', 'smartems'])]
class FirmwareController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiDuplicateTrait;
    use ApiListTrait;
    use ApiExportCsvTrait;
    use ApiExportExcelTrait;
    use UploadManagerTrait;
    use SecurityHelperTrait;

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
            $queryBuilder->andWhere('dt1.nameFirmware1 LIKE :featureNameValue OR dt2.nameFirmware2 LIKE :featureNameValue OR dt3.nameFirmware3 LIKE :featureNameValue');
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
        $object->setSecret(substr(Uuid::v4()->toBase32(), 0, 6));
        $object->setUuid($this->getUniqueUuid());

        if (SourceType::UPLOAD === $object->getSourceType()) {
            $tusFile = $this->uploadManager->getTusFile($object->getFilepath());
            $object->setMd5(md5_file($tusFile['file_path']));
        }

        if (SourceType::EXTERNAL_URL === $object->getSourceType()) {
            $object->setFilename(basename($object->getExternalUrl()));
        }

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        // After uploading filename will change (i.e. can be sluggified). Refresh filename
        if (SourceType::UPLOAD === $object->getSourceType()) {
            $object->setFilename(basename($object->getFilepath()));
        }

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }

    #[Rest\Post('/{id}/source/upload/edit', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit uploaded {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of uploaded {{ subjectLower }} to edit')]
    #[Api\RequestBody(content: new NA\Model(type: FirmwareEditUploadType::class))]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function editSourceUploadAction(Request $request, int $id)
    {
        $object = $this->find($id, FirmwareDeny::EDIT_SOURCE_UPLOAD);

        return $this->handleForm(FirmwareEditUploadType::class, $request, [$this, 'processEdit'], $object);
    }

    #[Rest\Post('/{id}/source/externalurl/edit', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit external {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of external {{ subjectLower }} to edit')]
    #[Api\RequestBody(content: new NA\Model(type: FirmwareEditExternalUrlType::class))]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function editSourceExternalUrlAction(Request $request, int $id)
    {
        $object = $this->find($id, FirmwareDeny::EDIT_SOURCE_EXTERNAL_URL);

        return $this->handleForm(FirmwareEditExternalUrlType::class, $request, [$this, 'processEdit'], $object);
    }

    protected function processEdit($object, FormInterface $form)
    {
        if (SourceType::EXTERNAL_URL === $object->getSourceType()) {
            $object->setFilename(basename($object->getExternalUrl()));
        }

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }

    protected function processDelete(object $object)
    {
        $filepath = null;
        if (SourceType::UPLOAD === $object->getSourceType()) {
            $filepath = $object->getFilepath();
        }

        $this->entityManager->remove($object);
        $this->entityManager->flush();

        if (null !== $filepath) {
            $fs = new Filesystem();

            if ($fs->exists($filepath)) {
                $fs->remove($filepath);
            }
        }
    }

    protected function processDuplicate($object)
    {
        $duplicatedObject = $this->getDuplicatedObject($object);
        $duplicatedObject->setSecret(substr(Uuid::v4()->toBase32(), 0, 6));
        $duplicatedObject->setUuid($this->getUniqueUuid());
        $duplicatedObject->setName($this->getUniqueCopiedString($object, 'name'));

        if (SourceType::UPLOAD === $duplicatedObject->getSourceType()) {
            $this->duplicateUploadedFile($object, $duplicatedObject, 'filepath');
        }

        $this->entityManager->persist($duplicatedObject);
        $this->entityManager->flush();

        return $duplicatedObject;
    }

    protected function getUniqueUuid(): string
    {
        $uuid = substr(Uuid::v4()->toBase32(), 0, 6);
        $count = $this->getRepository(Firmware::class)->count(['uuid' => $uuid]);

        return $count > 0 ? $this->getUniqueUuid() : $uuid;
    }
}
