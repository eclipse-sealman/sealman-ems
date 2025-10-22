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
use App\Deny\ImportFileDeny;
use App\Entity\ImportFile;
use App\Enum\ImportFileStatus;
use App\Form\ImportFileCreateType;
use App\Form\ImportFileEditType;
use App\Service\Helper\ImportDeviceManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;

#[Rest\Route('/importfile')]
#[Api\Resource(
    class: ImportFile::class,
    createFormClass: ImportFileCreateType::class,
    editFormClass: ImportFileEditType::class,
    denyClass: ImportFileDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'importFile:public', 'timestampable', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class ImportFileController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiEditTrait;
    use ApiListTrait;
    use ImportDeviceManagerTrait;

    protected function processCreate($object, FormInterface $form)
    {
        $object->setStatus(ImportFileStatus::UPLOADED);

        // Store anything. Will be updated after upload
        $object->setFilename($object->getFilepath());

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        // After uploading filename will change (i.e. can be sluggified). Refresh filename
        $object->setFilename(basename($object->getFilepath()));

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->importDeviceManager->parse($object);

        return $object;
    }

    #[Rest\Get('/{id}/progress', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get {{ subjectLower }} progress')]
    #[Api\ParameterPathId('ID of {{ subjectLower }}')]
    #[Api\Response200(
        description: 'Progress',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(property: 'pending', type: 'integer'),
            ]
        ),
    )]
    #[Api\Response404Id]
    public function progressAction(int $id)
    {
        $object = $this->find($id);

        return [
            'total' => $this->importDeviceManager->getTotal($object),
            'pending' => $this->importDeviceManager->getPending($object),
        ];
    }

    #[Rest\Get('/{id}/import/next/row', requirements: ['id' => '\d+'])]
    #[Api\Summary('Import next row for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }}')]
    #[Api\Response204('Next row imported successfully')]
    #[Api\Response404Id]
    public function importNextRowAction(int $id)
    {
        $object = $this->find($id, ImportFileDeny::IMPORT_NEXT_ROW);

        return $this->importDeviceManager->importNext($object);
    }
}
