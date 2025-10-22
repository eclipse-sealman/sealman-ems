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
use App\Deny\TemplateDenyHelperTrait;
use App\Deny\TemplateVersionDeny;
use App\Entity\Device;
use App\Entity\Template;
use App\Entity\TemplateVersion;
use App\Enum\TemplateVersionType as TemplateVersionTypeEnum;
use App\Form\TemplateVersionCreateType;
use App\Form\TemplateVersionSelectType;
use App\Form\TemplateVersionType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\AuditableManagerTrait;
use App\Trait\ApiDuplicateTrait;
use Carve\ApiBundle\Attribute\AddRoleBasedSerializerGroups;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Exception\RequestExecutionException;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/templateversion')]
#[Api\Resource(
    class: TemplateVersion::class,
    editFormClass: TemplateVersionType::class,
    denyClass: TemplateVersionDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'templateVersion:public', 'timestampable', 'blameable', 'deny'])]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN', ['templateVersion:admin'])]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN_VPN', ['templateVersion:adminVpn'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
#[Areas(['admin', 'smartems'])]
class TemplateVersionController extends AbstractApiController
{
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiListTrait;
    use ApiEditTrait;
    use ApiDuplicateTrait;
    use SecurityHelperTrait;
    use TemplateDenyHelperTrait;
    use AuditableManagerTrait;

    protected function modifyResponseObject(object $object): void
    {
        $this->removeNotOwnedAccessTags($object);
    }

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        if (!$this->isAllDevicesGranted()) {
            $queryBuilder->leftJoin($alias.'.template', 't');
            $queryBuilder->leftJoin('t.devices', 'd');
            $queryBuilder->andWhere($alias.'.createdBy = :user OR t.stagingTemplate = '.$alias.'.id OR t.productionTemplate = '.$alias.'.id');

            $this->applyUserAccessTagsQueryModification($queryBuilder, 'd', ' OR '.$alias.'.createdBy = :user OR t.createdBy = :user OR t.stagingTemplate = '.$alias.'.id OR t.productionTemplate = '.$alias.'.id');

            $queryBuilder->setParameter('user', $this->getUser());
        }
    }

    #[Rest\Post('/create/staging/{templateId}', requirements: ['templateId' => '\d+'])]
    #[Api\Summary('Create staging {{ subjectLower }} for template by ID')]
    #[Api\Parameter(name: 'templateId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of template')]
    #[Api\RequestBody(content: new NA\Model(type: TemplateVersionCreateType::class))]
    #[Api\Response200SubjectGroups('Returns created staging {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404('Template with specified ID was not found')]
    public function createAction(Request $request, int $templateId)
    {
        $template = $this->getRepository(Template::class)->find($templateId);
        if (!$template) {
            throw new NotFoundHttpException();
        }

        if (!$this->isAllDevicesGranted()) {
            if ($this->isAnyDeviceInaccessibleUsingTemplate($template)) {
                throw new RequestExecutionException('deny.templateVersion.createAccessDeniedTemplateOutsideAccessScope');
            }
        }

        $templateVersion = new TemplateVersion();
        $templateVersion->setType(TemplateVersionTypeEnum::STAGING);
        $templateVersion->setTemplate($template);
        $templateVersion->setDeviceType($template->getDeviceType());

        return $this->handleForm(TemplateVersionCreateType::class, $request, function ($object) {
            if (!$this->isGranted('ROLE_ADMIN_VPN')) {
                $object->setDeviceDescription(null);
                $object->setVirtualSubnetCidr(null);
                $object->setMasqueradeType(null);
                $object->setMasquerades(new ArrayCollection());
                $object->setEndpointDevices(new ArrayCollection());
            }
            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $this->modifyResponseObject($object);

            return $object;
        }, $templateVersion);
    }

    #[Rest\Post('/select/staging/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Select {{ subjectLower }} as staging')]
    #[Api\ParameterPathId]
    #[Api\RequestBody(content: new NA\Model(type: TemplateVersionSelectType::class))]
    #[Api\Response200SubjectGroups('Returns updated {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function selectStagingAction(Request $request, int $id)
    {
        $templateVersion = $this->find($id, TemplateVersionDeny::SELECT_STAGING);

        return $this->handleForm(TemplateVersionSelectType::class, $request, function ($object) {
            $template = $object->getTemplate();
            $template->setStagingTemplate($object);

            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $this->updateReinstallFlags($object);

            $this->modifyResponseObject($object);

            return $object;
        }, $templateVersion);
    }

    #[Rest\Post('/select/production/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Select {{ subjectLower }} as production')]
    #[Api\ParameterPathId]
    #[Api\RequestBody(content: new NA\Model(type: TemplateVersionSelectType::class))]
    #[Api\Response200SubjectGroups('Returns updated {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function selectProductionAction(Request $request, int $id)
    {
        $templateVersion = $this->find($id, TemplateVersionDeny::SELECT_PRODUCTION);

        return $this->handleForm(TemplateVersionSelectType::class, $request, function ($object) {
            $template = $object->getTemplate();
            $template->setProductionTemplate($object);

            if (TemplateVersionTypeEnum::STAGING === $object->getType()) {
                $template->setStagingTemplate(null);
                $object->setType(TemplateVersionTypeEnum::PRODUCTION);

                $this->entityManager->persist($object);
            }

            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $this->updateReinstallFlags($object);

            $this->modifyResponseObject($object);

            return $object;
        }, $templateVersion);
    }

    #[Rest\Get('/detach/staging/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Remove {{ subjectLower }} as staging')]
    #[Api\ParameterPathId]
    #[Api\Response200SubjectGroups('Returns updated {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function detachStagingAction(int $id)
    {
        $object = $this->find($id, TemplateVersionDeny::DETACH_STAGING);

        $template = $object->getTemplate();
        $template->setStagingTemplate(null);

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    #[Rest\Get('/detach/production/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Remove {{ subjectLower }} as production')]
    #[Api\ParameterPathId]
    #[Api\Response200SubjectGroups('Returns updated {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function detachProductionAction(int $id)
    {
        $object = $this->find($id, TemplateVersionDeny::DETACH_PRODUCTION);

        $template = $object->getTemplate();
        $template->setProductionTemplate(null);

        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    protected function processEdit($object, $form)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->updateReinstallFlags($object);

        return $object;
    }

    /**
     * This method updates reinstall flags for devices using template owning this templateVersion
     * word "device's" used below means only devices with template (owning this templateVersion) assigned.
     *
     * It works in following way:
     * If templateVersion is NOT used as staging or production template - NO device's flags are updated
     * If templateVersion is used as staging template - ONLY STAGING device's flags are updated
     * If templateVersion is used as production template and another staging template is used - ONLY PRODUCTION device's flags are updated
     * If templateVersion is used as production template and NO staging template is set - ALL device's flags are updated.
     */
    protected function updateReinstallFlags(TemplateVersion $object)
    {
        if (!$object->getDeviceType()) {
            return;
        }

        if (!$object->getTemplate()) {
            return;
        }

        // If template version is not used as staging or production reinstall flags will not be updated
        if ($object->getTemplate()->getStagingTemplate() !== $object && $object->getTemplate()->getProductionTemplate() !== $object) {
            return;
        }

        if ($object->getTemplate()->getStagingTemplate() == $object) {
            // If template version is used as staging only staging devices will be updated
            $staging = true;
        } elseif ($object->getTemplate()->getProductionTemplate() == $object && $object->getTemplate()->getStagingTemplate()) {
            // If template version is used as production and staging template is set only production devices will be updated
            $staging = false;
        } else {
            // If template version is used as production and staging template is NOT set all devices will be updated ($stagingFilter = false;)
            $staging = null;
        }

        $flags = [];
        if ($object->getReinstallFirmware1() && $object->getDeviceType()->getHasFirmware1()) {
            $flags[] = 'reinstallFirmware1';
        }
        if ($object->getReinstallFirmware2() && $object->getDeviceType()->getHasFirmware2()) {
            $flags[] = 'reinstallFirmware2';
        }
        if ($object->getReinstallFirmware3() && $object->getDeviceType()->getHasFirmware3()) {
            $flags[] = 'reinstallFirmware3';
        }
        if ($object->getReinstallConfig1() && $object->getDeviceType()->getHasConfig1() && !$object->getDeviceType()->getHasAlwaysReinstallConfig1()) {
            $flags[] = 'reinstallConfig1';
        }
        if ($object->getReinstallConfig2() && $object->getDeviceType()->getHasConfig2() && !$object->getDeviceType()->getHasAlwaysReinstallConfig2()) {
            $flags[] = 'reinstallConfig2';
        }
        if ($object->getReinstallConfig3() && $object->getDeviceType()->getHasConfig3() && !$object->getDeviceType()->getHasAlwaysReinstallConfig3()) {
            $flags[] = 'reinstallConfig3';
        }

        // No flags will be set no need to execute query
        if (0 === count($flags)) {
            return;
        }

        $template = $object->getTemplate();

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            foreach ($flags as $flag) {
                $queryBuilder = $this->getReinstallFlagsQueryBuilder($template, $staging);
                $queryBuilder->andWhere('d.'.$flag.' = :'.$flag);
                $queryBuilder->setParameter($flag, false);

                $this->auditableManager->createPartialBatchUpdate($queryBuilder, [$flag => false], [$flag => true]);
            }

            $queryBuilder = $this->getReinstallFlagsQueryBuilder($template, $staging);
            $queryBuilder->update();

            foreach ($flags as $flag) {
                $queryBuilder->set('d.'.$flag, true);
            }

            $queryBuilder->getQuery()->execute();

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    protected function getReinstallFlagsQueryBuilder(Template $template, $staging): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository(Device::class)->createQueryBuilder('d');
        $queryBuilder->andWhere('d.template = :template');
        $queryBuilder->setParameter('template', $template);

        if (null !== $staging) {
            $queryBuilder->andWhere('d.staging = :staging');
            $queryBuilder->setParameter('staging', $staging);
        }

        return $queryBuilder;
    }

    protected function processDuplicate($object)
    {
        $duplicatedObject = $this->getDuplicatedObject($object);
        $duplicatedObject->setType(TemplateVersionTypeEnum::STAGING);
        $duplicatedObject->setName($this->getUniqueCopiedString($object, 'name'));

        $this->duplicateCollection($object, $duplicatedObject, 'variables');

        $this->duplicateOwnedAccessTags($object, $duplicatedObject);

        if ($this->isAllVpnDevicesGranted()) {
            $this->duplicateCollection($object, $duplicatedObject, 'masquerades');
            $this->duplicateCollection($object, $duplicatedObject, 'endpointDevices');
        } else {
            $duplicatedObject->setDeviceDescription(null);
            $duplicatedObject->setVirtualSubnetCidr(null);
            $duplicatedObject->setMasqueradeType(null);
            $duplicatedObject->setMasquerades(new ArrayCollection());
            $duplicatedObject->setEndpointDevices(new ArrayCollection());
        }

        $this->entityManager->persist($duplicatedObject);
        $this->entityManager->flush();

        return $duplicatedObject;
    }
}
