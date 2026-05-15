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
use App\Deny\FirmwareDeny;
use App\Entity\DeviceType;
use App\Entity\Firmware;
use App\Entity\FirmwareHardwareFile;
use App\Enum\Feature;
use App\Enum\SourceType;
use App\Form\FirmwareCreateEnabledHardwareFilesType;
use App\Form\FirmwareCreateType;
use App\Form\FirmwareEditEnabledHardwareFilesType;
use App\Form\FirmwareEditExternalUrlType;
use App\Form\FirmwareEditUploadType;
use App\Model\ExportFirmware;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\FirmwareVersionSchemaManagerTrait;
use App\Service\Helper\TranslatorTrait;
use App\Service\Helper\UploadManagerTrait;
use App\Trait\ApiDuplicateTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Model\ExportCsvQueryInterface;
use Carve\ApiBundle\Model\ExportExcelQueryInterface;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiExportCsvTrait;
use Carve\ApiBundle\Trait\ApiExportExcelTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Carve\ApiBundle\View\ExportCsvView;
use Carve\ApiBundle\View\ExportExcelView;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

#[Rest\Route('/firmware')]
#[Api\Resource(
    class: Firmware::class,
    createFormClass: FirmwareCreateType::class,
    denyClass: FirmwareDeny::class,
    listFormFilterByAppend: ['featureName'],
    exportFormFieldAppend: ['hardware'],
)]
#[Rest\View(serializerGroups: ['identification', 'firmware:public', 'timestampable', 'blameable', 'deny'])]
#[IsGrantedOr(['ROLE_ADMIN', 'ROLE_SMARTEMS'])]
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
    use TranslatorTrait;
    use FirmwareVersionSchemaManagerTrait;

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

    #[Rest\Get('/required/firmware/options/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get firmwares available as required firmware options for given firmware ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return')]
    #[Api\Response200ArraySubjectGroups(Firmware::class)]
    #[Rest\View(serializerGroups: ['identification'])]
    public function getRequiredFirmwareOptionsByFirmwareIdAction(int $id)
    {
        $firmware = $this->find($id, FirmwareDeny::EDIT);

        $feature = $firmware->getFeature();
        $deviceType = $firmware->getDeviceType();

        $options = $this->getUnsortedRequiredFirmwareOptions($feature, $deviceType);

        if (null !== $firmware->getRequiredFirmware()) {
            if (!in_array($firmware->getRequiredFirmware(), $options, true)) {
                $options[] = $firmware->getRequiredFirmware();
            }
        }

        if (in_array($firmware, $options, true)) {
            $options = array_filter($options, fn (Firmware $f) => $f->getId() !== $firmware->getId());
        }

        $firmwareSchema = $deviceType->getFirmwareSchema($feature);
        $options = $this->firmwareVersionSchemaManager->sortFirmwares($options, $firmwareSchema);

        return $options;
    }

    #[Rest\Get('/required/firmware/options/{feature}/{deviceTypeId}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get firmwares available as required firmware options for feature and device type ID')]
    #[Api\Parameter(name: 'feature', in: 'path', schema: new OA\Schema(type: 'string'), description: 'Feature')]
    #[Api\Parameter(name: 'deviceTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device type')]
    #[Api\Response200ArraySubjectGroups(Firmware::class)]
    #[Rest\View(serializerGroups: ['identification'])]
    public function getRequiredFirmwareOptionsByFeatureAndDeviceTypeAction(string $feature, int $deviceTypeId)
    {
        try {
            $featureAsEnum = Feature::from($feature);
        } catch (\ValueError $e) {
            throw new NotFoundHttpException();
        }

        $deviceType = $this->getRepository(DeviceType::class)->find($deviceTypeId);
        if (!$deviceType) {
            throw new NotFoundHttpException();
        }

        $options = $this->getUnsortedRequiredFirmwareOptions($featureAsEnum, $deviceType);

        $firmwareSchema = $deviceType->getFirmwareSchema($featureAsEnum);
        $options = $this->firmwareVersionSchemaManager->sortFirmwares($options, $firmwareSchema);

        return $options;
    }

    protected function getUnsortedRequiredFirmwareOptions(Feature $feature, DeviceType $deviceType): array
    {
        $queryBuilder = $this->getRepository(Firmware::class)->createQueryBuilder('f');
        $queryBuilder->andWhere('f.feature = :feature');
        $queryBuilder->andWhere('f.deviceType = :deviceType');
        $queryBuilder->setParameter('feature', $feature);
        $queryBuilder->setParameter('deviceType', $deviceType);
        $queryBuilder->orderBy('f.version', 'DESC');

        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'f');

        return $queryBuilder->getQuery()->getResult();
    }

    #[Rest\Get('/update/path/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Get {{ subjectLower }} update path array by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return')]
    #[Api\Response200ArraySubjectGroups(Firmware::class, 'Returns array of {{ subjectLower }} update path objects starting from given ID')]
    #[Api\Response404Id]
    public function getUpdatePathAction(int $id)
    {
        $object = $this->find($id, FirmwareDeny::SHOW_UPDATE_PATH);

        $updatePathArray = [];

        do {
            $this->modifyResponseObject($object);
            $updatePathArray[] = $object;
        } while ($object = $object->getRequiredFirmware());

        return $updatePathArray;
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

        $this->modifyResponseObject($object);

        return $object;
    }

    #[Rest\Post('/enabledhardwarefiles/create')]
    #[Api\Summary('Create {{ subjectLower }} with enabled hardware files')]
    #[Api\RequestBody(content: new NA\Model(type: FirmwareCreateEnabledHardwareFilesType::class))]
    #[Api\Response200SubjectGroups('Returns created {{ subjectLower }} with enabled hardware files')]
    #[Api\Response400]
    public function createEnabledHardwareFilesAction(Request $request)
    {
        return $this->handleForm(FirmwareCreateEnabledHardwareFilesType::class, $request, function ($object, FormInterface $form) {
            $object->setEnableHardwareFiles(true);

            return $this->processCreate($object, $form);
        });
    }

    #[Rest\Post('/{id}/enabledhardwarefiles/edit', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} with enabled hardware files by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} with enabled hardware files to edit')]
    #[Api\RequestBody(content: new NA\Model(type: FirmwareEditEnabledHardwareFilesType::class))]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }} with enabled hardware files')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function editEnabledHardwareFilesAction(Request $request, int $id)
    {
        $object = $this->find($id, FirmwareDeny::EDIT_HARDWARE_FILES);

        $allowEditRequiredFirmware = !$this->isDenied($this->getDenyClass(), FirmwareDeny::EDIT_REQUIRED_FIRMWARE, $object);

        return $this->handleForm(
            FirmwareEditEnabledHardwareFilesType::class,
            $request,
            [$this, 'processEdit'],
            $object,
            [
                'allowEditRequiredFirmware' => $allowEditRequiredFirmware,
                'requiredFirmwareId' => $object->getRequiredFirmware()?->getId(),
            ]
        );
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

        $allowEditRequiredFirmware = !$this->isDenied($this->getDenyClass(), FirmwareDeny::EDIT_REQUIRED_FIRMWARE, $object);

        return $this->handleForm(
            FirmwareEditUploadType::class,
            $request,
            [$this, 'processEdit'],
            $object,
            [
                'allowEditRequiredFirmware' => $allowEditRequiredFirmware,
                'requiredFirmwareId' => $object->getRequiredFirmware()?->getId(),
            ]
        );
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

        $allowEditRequiredFirmware = !$this->isDenied($this->getDenyClass(), FirmwareDeny::EDIT_REQUIRED_FIRMWARE, $object);

        return $this->handleForm(
            FirmwareEditExternalUrlType::class,
            $request,
            [$this, 'processEdit'],
            $object,
            [
                'allowEditRequiredFirmware' => $allowEditRequiredFirmware,
                'requiredFirmwareId' => $object->getRequiredFirmware()?->getId(),
            ]
        );
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
        $filepaths = [];
        if (SourceType::UPLOAD === $object->getSourceType()) {
            $filepaths[] = $object->getFilepath();
        }

        foreach ($object->getHardwareFiles() as $hardwareFile) {
            if (SourceType::UPLOAD === $hardwareFile->getSourceType()) {
                $filepaths[] = $hardwareFile->getFilepath();
            }
        }

        $this->entityManager->remove($object);
        $this->entityManager->flush();

        if (count($filepaths) > 0) {
            $fs = new Filesystem();

            foreach ($filepaths as $filepath) {
                if ($fs->exists($filepath)) {
                    $fs->remove($filepath);
                }
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

        foreach ($object->getHardwareFiles() as $hardwareFile) {
            $duplicatedHardwareFile = $this->getDuplicatedObject($hardwareFile);
            $duplicatedHardwareFile->setFirmware($duplicatedObject);
            $duplicatedObject->getHardwareFiles()->add($duplicatedHardwareFile);

            if (SourceType::UPLOAD === $duplicatedHardwareFile->getSourceType()) {
                $this->duplicateUploadedFile($hardwareFile, $duplicatedHardwareFile, 'filepath');
            }

            $this->entityManager->persist($duplicatedHardwareFile);
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

    protected function processExportCsv(ExportCsvQueryInterface $exportCsvQuery, FormInterface $form)
    {
        $queryBuilder = $this->getExportQueryBuilder($exportCsvQuery);
        $results = $queryBuilder->getQuery()->getResult();

        $fieldNames = $this->getFieldsFieldChoices();
        $exportResults = [];

        foreach ($results as $result) {
            foreach ($this->getFirmwareExportResults($result, $fieldNames) as $exportResult) {
                $exportResults[] = $exportResult;
            }
        }

        return new ExportCsvView($exportResults, $exportCsvQuery->getFields(), $exportCsvQuery->getFilename());
    }

    protected function processExportExcel(ExportExcelQueryInterface $exportExcelQuery, FormInterface $form)
    {
        $queryBuilder = $this->getExportQueryBuilder($exportExcelQuery);
        $results = $queryBuilder->getQuery()->getResult();

        $fieldNames = $this->getFieldsFieldChoices();
        $exportResults = [];

        foreach ($results as $result) {
            foreach ($this->getFirmwareExportResults($result, $fieldNames) as $exportResult) {
                $exportResults[] = $exportResult;
            }
        }

        return new ExportExcelView($exportResults, $exportExcelQuery->getFields(), $exportExcelQuery->getFilename(), $exportExcelQuery->getSheetName());
    }

    /**
     * Returns array of export results (App\Model\ExportFirmware) for given firmware.
     */
    protected function getFirmwareExportResults(Firmware $firmware, array $fieldNames): array
    {
        if ($firmware->getEnableHardwareFiles()) {
            $exportResults = [];

            foreach ($firmware->getHardwareFiles() as $hardwareFile) {
                $exportResults[] = $this->getFirmwareExportResult($firmware, $fieldNames, $hardwareFile);
            }

            return $exportResults;
        }

        return [$this->getFirmwareExportResult($firmware, $fieldNames)];
    }

    /**
     * Returns export result (App\Model\ExportFirmware) for given firmware and optionally firmware hardware file.
     */
    protected function getFirmwareExportResult(Firmware $firmware, array $fieldNames, ?FirmwareHardwareFile $hardwareFile = null): ExportFirmware
    {
        $sharedFieldNames = [
            'sourceType',
            'md5',
            'filename',
            'filepath',
            'externalUrl',
            'createdAt',
            'updatedAt',
            'createdBy',
            'updatedBy',
        ];

        $hardwareFileFieldNames = [
            'hardware',
        ];

        $result = new ExportFirmware();

        foreach ($fieldNames as $fieldName) {
            $getter = 'get'.ucfirst($fieldName);
            $setter = 'set'.ucfirst($fieldName);

            if (in_array($fieldName, $hardwareFileFieldNames)) {
                if (!$hardwareFile) {
                    continue;
                }

                $this->setExportedValue($fieldName, $result, $hardwareFile);
                continue;
            }

            if ($hardwareFile && in_array($fieldName, $sharedFieldNames)) {
                $this->setExportedValue($fieldName, $result, $hardwareFile);
                continue;
            }

            $this->setExportedValue($fieldName, $result, $firmware);
        }

        return $result;
    }

    /**
     * Sets exported value in $target object based on $origin object.
     */
    protected function setExportedValue(string $fieldName, object $target, object $origin): void
    {
        $getter = 'get'.ucfirst($fieldName);
        $setter = 'set'.ucfirst($fieldName);

        if (!method_exists($origin, $getter)) {
            throw new \Exception('Method '.$getter.' does not exist in '.get_class($origin).' which is needed for export');
        }
        if (!method_exists($target, $setter)) {
            throw new \Exception('Method '.$setter.' does not exist in '.get_class($target).' which is needed for export');
        }

        if ('deny' === $fieldName) {
            // Manual solution due to: Typed property App\Entity\Firmware::$deny must not be accessed before initialization
            $target->setDeny(null);

            return;
        }

        if ($origin->$getter() instanceof \BackedEnum) {
            $target->$setter($this->trans('enum.firmware.'.$fieldName.'.'.$origin->$getter()->value));

            return;
        }

        $target->$setter($origin->$getter());
    }
}
