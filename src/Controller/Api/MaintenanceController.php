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
use App\Deny\MaintenanceDeny;
use App\Entity\Maintenance;
use App\Enum\MaintenanceStatus;
use App\Enum\MaintenanceType as MaintenanceTypeEnum;
use App\Form\MaintenanceType;
use App\Form\MaintenanceUploadType;
use App\Service\Helper\ConfigurationManagerTrait;
use App\Service\Helper\EncryptionManagerTrait;
use App\Service\Helper\MaintenanceManagerTrait;
use App\Service\Helper\UploadManagerTrait;
use App\Service\MaintenanceManager;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/maintenance')]
#[Api\Resource(
    class: Maintenance::class,
    createFormClass: MaintenanceType::class,
    denyClass: MaintenanceDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'maintenance:public', 'loglevel', 'timestampable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class MaintenanceController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiListTrait;
    use MaintenanceManagerTrait;
    use ConfigurationManagerTrait;
    use UploadManagerTrait;
    use EncryptionManagerTrait;

    protected function processCreate($object, FormInterface $form)
    {
        if ($object->getBackupPassword()) {
            $object->setBackupPassword($this->encryptionManager->encrypt($object->getBackupPassword()));
        }

        if ($object->getRestorePassword()) {
            $object->setRestorePassword($this->encryptionManager->encrypt($object->getRestorePassword()));
        }

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }

    protected function processDelete(object $object)
    {
        $fullPath = null;
        if (MaintenanceStatus::SUCCESS === $object->getStatus() && MaintenanceTypeEnum::BACKUP === $object->getType()) {
            $fullPath = MaintenanceManager::BACKUP_DIRECTORY.'/'.$object->getFilepath();
        }

        $this->entityManager->remove($object);
        $this->entityManager->flush();

        $fs = new Filesystem();
        if ($fullPath && $fs->exists($fullPath)) {
            $fs->remove($fullPath);
        }
    }

    #[Rest\Post('/upload')]
    #[Api\Summary('Upload {{ subjectLower }} backup')]
    #[Api\RequestBody(content: new NA\Model(type: MaintenanceUploadType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    public function uploadAction(Request $request)
    {
        return $this->handleForm(MaintenanceUploadType::class, $request, function ($object) {
            $this->uploadManager->upload($object, 'filepath');
        });
    }

    #[Rest\Get('/restore/archive/file/paths')]
    #[Api\Summary('Get {{ subjectLower }} restore archive filepaths')]
    #[Api\Response200(description: 'Restore archive filepaths', content: new OA\JsonContent(
        type: 'array',
        items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'representation', type: 'string'),
        ])),
    )]
    public function restoreArchiveFilepathsAction()
    {
        return $this->maintenanceManager->getRestoreArchiveFilepaths();
    }

    #[Rest\Get('/{id}/download')]
    #[Api\Summary('Download {{ subjectLower }} backup')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download')]
    #[Api\Response200(description: 'Backup file', content: new OA\MediaType(mediaType: 'binary', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    public function downloadAction(int $id)
    {
        $maintenance = $this->find($id, MaintenanceDeny::DOWNLOAD);

        $fullPath = MaintenanceManager::BACKUP_DIRECTORY.'/'.$maintenance->getFilepath();
        $fs = new Filesystem();

        if (!$fs->exists($fullPath)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($fullPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($fullPath));

        return $response;
    }

    #[Rest\Get('/mode/enable')]
    #[Api\Summary('Enable maintenance mode')]
    #[Api\Response204('Maintenance mode successfully enabled')]
    public function maintenanceModeEnableAction()
    {
        $configuration = $this->getConfiguration();
        $configuration->setMaintenanceMode(true);

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
    }

    #[Rest\Get('/mode/disable')]
    #[Api\Summary('Disable maintenance mode')]
    #[Api\Response204('Maintenance mode successfully disabled')]
    public function maintenanceModeDisableAction()
    {
        $configuration = $this->getConfiguration();
        $configuration->setMaintenanceMode(false);

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();
    }
}
