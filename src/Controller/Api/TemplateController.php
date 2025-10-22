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
use App\Deny\TemplateDeny;
use App\Entity\Template;
use App\Form\TemplateCreateType;
use App\Form\TemplateEditType;
use App\Security\SecurityHelperTrait;
use Carve\ApiBundle\Attribute\AddRoleBasedSerializerGroups;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/template')]
#[Api\Resource(
    class: Template::class,
    createFormClass: TemplateCreateType::class,
    editFormClass: TemplateEditType::class,
    denyClass: TemplateDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'template:public', 'templateVersion:public', 'timestampable', 'blameable', 'deny'])]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN', ['templateVersion:admin'])]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN_VPN', ['templateVersion:adminVpn'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
#[Areas(['admin', 'smartems'])]
class TemplateController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiEditTrait;
    use ApiGetTrait;
    use ApiListTrait;
    use SecurityHelperTrait;

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        if (!$this->isAllDevicesGranted()) {
            $queryBuilder->leftJoin($alias.'.devices', 'd');

            $queryBuilder->leftJoin($alias.'.templateVersions', 'tv');

            // There is difference between query in OptionsController:templatesAction() and in this controller
            // User can use templates only templates that user created or got access by having access to device with said template
            // In this controller there is possiblity to see templates without access (comment above), where user created template version
            // those templates cannot be used in devices, but template version should be manageable (so template has to be seen in list)
            $this->applyUserAccessTagsQueryModification($queryBuilder, 'd', ' OR '.$alias.'.createdBy = :user OR tv.createdBy = :user');

            $queryBuilder->setParameter('user', $this->getUser());
        }
    }
}
