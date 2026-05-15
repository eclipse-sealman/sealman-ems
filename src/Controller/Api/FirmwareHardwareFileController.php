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
use App\Attribute\IsGrantedOr;
use App\Deny\FirmwareHardwareFileDeny;
use App\Entity\Firmware;
use App\Entity\FirmwareHardwareFile;
use App\Enum\SourceType;
use App\Form\FirmwareHardwareFileCreateType;
use App\Form\FirmwareHardwareFileEditExternalUrlType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\UploadManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/firmwarehardwarefile')]
#[Api\Resource(
    class: FirmwareHardwareFile::class,
    createFormClass: FirmwareHardwareFileCreateType::class,
    denyClass: FirmwareHardwareFileDeny::class,
)]
#[Rest\View(serializerGroups: ['identification', 'firmwareHardwareFile:public', 'timestampable', 'blameable', 'deny'])]
#[IsGrantedOr(['ROLE_ADMIN', 'ROLE_SMARTEMS'])]
#[Areas(['admin', 'smartems'])]
class FirmwareHardwareFileController extends AbstractApiController
{
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiListTrait;
    use UploadManagerTrait;
    use SecurityHelperTrait;

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $queryBuilder->leftJoin($alias.'.firmware', 'f');
        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'f');
    }

    #[Rest\Post('/{firmwareId}/create')]
    #[Api\Summary('Create {{ subjectLower }} for firmware by ID')]
    #[Api\RequestBodyCreate]
    #[Api\Response200SubjectGroups('Returns created {{ subjectLower }}')]
    #[Api\Response400]
    public function createAction(Request $request, int $firmwareId)
    {
        $firmware = $this->getRepository(Firmware::class)->find($firmwareId);
        if (!$firmware) {
            throw new NotFoundHttpException();
        }

        $firmwareHardwareFile = new FirmwareHardwareFile();
        $firmwareHardwareFile->setFirmware($firmware);

        $denyClass = $this->getDenyClass();
        if ($this->isDenied($denyClass, FirmwareHardwareFileDeny::CREATE, $firmwareHardwareFile)) {
            throw new AccessDeniedHttpException();
        }

        return $this->handleForm($this->getCreateFormClass(), $request, [$this, 'processCreate'], $firmwareHardwareFile);
    }

    protected function processCreate($object, FormInterface $form)
    {
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

    #[Rest\Post('/{id}/source/externalurl/edit', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} with external URL as sourceType by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} with external URL as sourceType to edit')]
    #[Api\RequestBody(content: new NA\Model(type: FirmwareHardwareFileEditExternalUrlType::class))]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }} with external URL as sourceType')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function editSourceExternalUrlAction(Request $request, int $id)
    {
        $object = $this->find($id, FirmwareHardwareFileDeny::EDIT_SOURCE_EXTERNAL_URL);

        return $this->handleForm(FirmwareHardwareFileEditExternalUrlType::class, $request, [$this, 'processEdit'], $object);
    }

    protected function processEdit($object, FormInterface $form)
    {
        // Only entities with sourceType EXTERNAL_URL can be edited

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
}
