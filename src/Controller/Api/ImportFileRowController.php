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
use App\Deny\ImportFileRowDeny;
use App\Entity\ImportFileRow;
use App\Entity\ImportFileRowVariable;
use App\Form\BatchAccessTagsType;
use App\Form\BatchFlagType;
use App\Form\BatchLabelsType;
use App\Form\BatchTemplateChangeType;
use App\Form\BatchVariableAddType;
use App\Form\BatchVariableDeleteType;
use App\Form\ImportFileRowAccessTagsType;
use App\Form\ImportFileRowEnabledType;
use App\Form\ImportFileRowLabelsType;
use App\Form\ImportFileRowReinstallConfig1Type;
use App\Form\ImportFileRowReinstallConfig2Type;
use App\Form\ImportFileRowReinstallConfig3Type;
use App\Form\ImportFileRowTemplateType;
use App\Service\Helper\ValidatorTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Enum\BatchResultStatus;
use Carve\ApiBundle\Model\BatchResult;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

#[Rest\Route('/importfilerow')]
#[Api\Resource(
    class: ImportFileRow::class,
    denyClass: ImportFileRowDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'importFileRow:public', 'timestampable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class ImportFileRowController extends AbstractApiController
{
    use ApiListTrait;
    use ValidatorTrait;

    #[Rest\Post('/batch/template/change')]
    #[Api\Summary('Change template to multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchTemplateChangeType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchTemplateChangeAction(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $template = $form->get('template')->getData();

            if (!$template) {
                $row->setTemplate(null);

                $this->entityManager->persist($row);

                return;
            }

            if ($row->getDeviceType() !== $template->getDeviceType()) {
                return new BatchResult($row, BatchResultStatus::ERROR, 'batch.importFileRow.templateChange.error.deviceTypeMismatch');
            }

            $row->setTemplate($template);

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::TEMPLATE_CHANGE, null, BatchTemplateChangeType::class);
    }

    #[Rest\Post('/batch/variable/add')]
    #[Api\Summary('Add variable to multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchVariableAddType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchVariableAddAction(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $name = $form->get('name')->getData();
            $variableValue = $form->get('variableValue')->getData();

            $queryBuilder = $this->getRepository(ImportFileRowVariable::class)->createQueryBuilder('ifrv');
            $queryBuilder->andWhere('ifrv.row = :row');
            // BINARY is used to achieve case sensitive search
            $queryBuilder->andWhere('ifrv.name = BINARY(:name)');
            $queryBuilder->setParameter('row', $row);
            $queryBuilder->setParameter('name', $name);

            $importFileRowVariable = $queryBuilder->getQuery()->getOneOrNullResult();
            $found = $importFileRowVariable ? true : false;

            if (!$found) {
                $importFileRowVariable = new ImportFileRowVariable();
                $importFileRowVariable->setName($name);
                $row->addVariable($importFileRowVariable);
            }

            $importFileRowVariable->setVariableValue($variableValue);

            $errors = $this->validator->validate($importFileRowVariable, null, ['Default', 'importFileRow:import']);
            if (0 === count($errors)) {
                $this->entityManager->persist($importFileRowVariable);
            } else {
                if (!$found) {
                    // Only removeVariable when $importFileRowVariable has just been created
                    $row->removeVariable($importFileRowVariable);
                }

                // Pick first error as representative
                $errorRepresentative = $errors[0];

                return new BatchResult($row, BatchResultStatus::ERROR, $errorRepresentative->getMessage(), $errorRepresentative->getParameters());
            }
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::VARIABLE_ADD, null, BatchVariableAddType::class);
    }

    #[Rest\Post('/batch/variable/delete')]
    #[Api\Summary('Delete variable from multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchVariableDeleteType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchVariableDeleteAction(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $name = $form->get('name')->getData();

            $queryBuilder = $this->getRepository(ImportFileRowVariable::class)->createQueryBuilder('ifrv');
            $queryBuilder->andWhere('ifrv.row = :row');
            // BINARY is used to achieve case sensitive search
            $queryBuilder->andWhere('ifrv.name = BINARY(:name)');
            $queryBuilder->setParameter('row', $row);
            $queryBuilder->setParameter('name', $name);

            $importFileRowVariable = $queryBuilder->getQuery()->getOneOrNullResult();
            if (!$importFileRowVariable) {
                return new BatchResult($row, BatchResultStatus::SKIPPED, 'batch.importFileRow.variableDelete.skipped.missing');
            }

            $this->entityManager->remove($importFileRowVariable);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::VARIABLE_DELETE, null, BatchVariableDeleteType::class);
    }

    #[Rest\Post('/batch/accesstags/add')]
    #[Api\Summary('Add access tags to multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchAccessTagsType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchAccessTagsAddAction(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $accessTags = $form->get('accessTags')->getData();

            foreach ($accessTags as $accessTag) {
                $row->addAccessTag($accessTag);
            }

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT, null, BatchAccessTagsType::class);
    }

    #[Rest\Post('/batch/accesstags/delete')]
    #[Api\Summary('Remove access tags from multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchAccessTagsType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchAccessTagsDeleteAction(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $accessTags = $form->get('accessTags')->getData();

            foreach ($accessTags as $accessTag) {
                $row->removeAccessTag($accessTag);
            }

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT, null, BatchAccessTagsType::class);
    }

    #[Rest\Post('/batch/labels/add')]
    #[Api\Summary('Add labels to multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchLabelsType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchLabelsAddAction(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $labels = $form->get('labels')->getData();

            foreach ($labels as $label) {
                $row->addLabel($label);
            }

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT, null, BatchLabelsType::class);
    }

    #[Rest\Post('/batch/labels/delete')]
    #[Api\Summary('Remove labels from multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchLabelsType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchLabelsDeleteAction(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $labels = $form->get('labels')->getData();

            foreach ($labels as $label) {
                $row->removeLabel($label);
            }

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT, null, BatchLabelsType::class);
    }

    #[Rest\Post('/batch/reinstallconfig1')]
    #[Api\Summary('Update reinstall primary config in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchReinstallConfig1Action(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $row->getDeviceType();
            if (!$deviceType) {
                return new BatchResult($row, BatchResultStatus::ERROR, 'validation.importFileRow.deviceTypeMissing');
            }

            if (!$deviceType->getHasConfig1()) {
                return new BatchResult($row, BatchResultStatus::ERROR, 'validation.importFileRow.config1Disabled');
            }
            if ($deviceType->getHasAlwaysReinstallConfig1()) {
                return new BatchResult($row, BatchResultStatus::SKIPPED, 'validation.importFileRow.config1Always');
            }

            $row->setReinstallConfig1($flag);

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/reinstallconfig2')]
    #[Api\Summary('Update reinstall secondary config in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchReinstallConfig2Action(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $row->getDeviceType();
            if (!$deviceType) {
                return new BatchResult($row, BatchResultStatus::ERROR, 'validation.importFileRow.deviceTypeMissing');
            }

            if (!$deviceType->getHasConfig2()) {
                return new BatchResult($row, BatchResultStatus::ERROR, 'validation.importFileRow.config2Disabled');
            }
            if ($deviceType->getHasAlwaysReinstallConfig2()) {
                return new BatchResult($row, BatchResultStatus::SKIPPED, 'validation.importFileRow.config2Always');
            }

            $row->setReinstallConfig2($flag);

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/reinstallconfig3')]
    #[Api\Summary('Update reinstall tertiary config in multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch(content: new NA\Model(type: BatchFlagType::class))]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchReinstallConfig3Action(Request $request)
    {
        $process = function (ImportFileRow $row, FormInterface $form) {
            $flag = $form->get('flag')->getData();

            $deviceType = $row->getDeviceType();
            if (!$deviceType) {
                return new BatchResult($row, BatchResultStatus::ERROR, 'validation.importFileRow.deviceTypeMissing');
            }

            if (!$deviceType->getHasConfig3()) {
                return new BatchResult($row, BatchResultStatus::ERROR, 'validation.importFileRow.config3Disabled');
            }
            if ($deviceType->getHasAlwaysReinstallConfig3()) {
                return new BatchResult($row, BatchResultStatus::SKIPPED, 'validation.importFileRow.config3Always');
            }

            $row->setReinstallConfig3($flag);

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT, null, BatchFlagType::class);
    }

    #[Rest\Post('/batch/enable')]
    #[Api\Summary('Enable multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchEnableAction(Request $request)
    {
        $process = function (ImportFileRow $row) {
            $row->setEnabled(true);

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT);
    }

    #[Rest\Post('/batch/disable')]
    #[Api\Summary('Disable multiple {{ subjectPluralLower }}')]
    #[Api\RequestBodyBatch]
    #[Api\Response200BatchResults]
    #[Api\Response400]
    public function batchDisableAction(Request $request)
    {
        $process = function (ImportFileRow $row) {
            $row->setEnabled(false);

            $this->entityManager->persist($row);
        };

        return $this->handleBatchForm($process, $request, ImportFileRowDeny::EDIT);
    }

    #[Rest\Post('/{id}/template', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} template')]
    #[Api\RequestBody(content: new NA\Model(type: ImportFileRowTemplateType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    public function editTemplateAction(Request $request, int $id)
    {
        $object = $this->find($id, ImportFileRowDeny::EDIT);

        return $this->handleForm(ImportFileRowTemplateType::class, $request, [$this, 'processEdit'], $object);
    }

    #[Rest\Post('/{id}/accesstags', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} access tags')]
    #[Api\RequestBody(content: new NA\Model(type: ImportFileRowAccessTagsType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    public function editAccessTagsAction(Request $request, int $id)
    {
        $object = $this->find($id, ImportFileRowDeny::EDIT);

        return $this->handleForm(ImportFileRowAccessTagsType::class, $request, [$this, 'processEdit'], $object);
    }

    #[Rest\Post('/{id}/labels', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} labels')]
    #[Api\RequestBody(content: new NA\Model(type: ImportFileRowLabelsType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    public function editLabelsAction(Request $request, int $id)
    {
        $object = $this->find($id, ImportFileRowDeny::EDIT);

        return $this->handleForm(ImportFileRowLabelsType::class, $request, [$this, 'processEdit'], $object);
    }

    #[Rest\Post('/{id}/reinstallconfig1', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} reinstall primary config')]
    #[Api\RequestBody(content: new NA\Model(type: ImportFileRowReinstallConfig1Type::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    public function editReinstallConfig1Action(Request $request, int $id)
    {
        $object = $this->find($id, ImportFileRowDeny::EDIT);

        return $this->handleForm(ImportFileRowReinstallConfig1Type::class, $request, [$this, 'processEdit'], $object);
    }

    #[Rest\Post('/{id}/reinstallconfig2', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} reinstall secondary config')]
    #[Api\RequestBody(content: new NA\Model(type: ImportFileRowReinstallConfig2Type::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    public function editReinstallConfig2Action(Request $request, int $id)
    {
        $object = $this->find($id, ImportFileRowDeny::EDIT);

        return $this->handleForm(ImportFileRowReinstallConfig2Type::class, $request, [$this, 'processEdit'], $object);
    }

    #[Rest\Post('/{id}/reinstallconfig3', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} reinstall tertiary config')]
    #[Api\RequestBody(content: new NA\Model(type: ImportFileRowReinstallConfig3Type::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    public function editReinstallConfig3Action(Request $request, int $id)
    {
        $object = $this->find($id, ImportFileRowDeny::EDIT);

        return $this->handleForm(ImportFileRowReinstallConfig3Type::class, $request, [$this, 'processEdit'], $object);
    }

    #[Rest\Post('/{id}/enabled', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} enabled')]
    #[Api\RequestBody(content: new NA\Model(type: ImportFileRowEnabledType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    public function editEnabledAction(Request $request, int $id)
    {
        $object = $this->find($id, ImportFileRowDeny::EDIT);

        return $this->handleForm(ImportFileRowEnabledType::class, $request, [$this, 'processEdit'], $object);
    }

    protected function processEdit($object)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }
}
