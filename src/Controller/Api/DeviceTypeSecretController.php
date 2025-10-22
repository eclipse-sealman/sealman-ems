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
use App\Deny\DeviceTypeSecretDeny;
use App\Entity\DeviceSecret;
use App\Entity\DeviceTypeSecret;
use App\Enum\SecretValueBehaviour;
use App\Form\DeviceTypeSecretCreateType;
use App\Form\DeviceTypeSecretEditType;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

#[Rest\Route('/devicetypesecret')]
#[Api\Resource(
    class: DeviceTypeSecret::class,
    createFormClass: DeviceTypeSecretCreateType::class,
    editFormClass: DeviceTypeSecretEditType::class,
    denyClass: DeviceTypeSecretDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'deviceTypeSecret:public', 'timestampable', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class DeviceTypeSecretController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiListTrait;

    #[Rest\Post('/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to edit')]
    #[Api\RequestBodyEdit]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function editAction(Request $request, int $id)
    {
        $object = $this->find($id, AbstractApiObjectDeny::EDIT);

        return $this->handleForm($this->getEditFormClass(), $request, function ($object, FormInterface $form) {
            // This is a replicated logic from App\Form\DeviceTypeSecretEditType
            // Right now there is no solution for clearing values of removed fields
            // This is a task for the future
            if (!$object->getUseAsVariable()) {
                $object->setVariableNamePrefix(null);
                $object->setSecretValueBehaviour(null);
                $object->setSecretValueRenewAfterDays(null);
            }

            if (!SecretValueBehaviour::isRenew($object->getSecretValueBehaviour())) {
                $object->setSecretValueRenewAfterDays(null);
            }

            if (SecretValueBehaviour::NONE === $object->getSecretValueBehaviour()) {
                $object->setManualForceRenewal(false);
            }

            if (!$object->getManualEdit()) {
                $object->setManualEditRenewReminder(false);
                $object->setManualEditRenewReminderAfterDays(null);
            }

            if (!$object->getManualEditRenewReminder()) {
                $object->setManualEditRenewReminderAfterDays(null);
            }

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            if (!$object->getManualForceRenewal()) {
                $queryBuilder = $this->entityManager->getRepository(DeviceSecret::class)->createQueryBuilder('ds');
                $queryBuilder->update(DeviceSecret::class, 'ds');
                $queryBuilder->set('ds.forceRenewal', 0); // Cannot use false here - it generates error - don't know why
                $queryBuilder->andWhere('ds.forceRenewal = :forceRenewal');
                $queryBuilder->setParameter('forceRenewal', true);
                $queryBuilder->andWhere('ds.deviceTypeSecret = :deviceTypeSecret');
                $queryBuilder->setParameter('deviceTypeSecret', $object);

                $queryBuilder->getQuery()->execute();
            }
            $this->entityManager->flush();

            $this->modifyResponseObject($object);

            return $object;
        }, $object);
    }
}
