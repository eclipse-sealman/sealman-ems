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
use App\Deny\SecretLogDeny;
use App\Entity\SecretLog;
use App\Service\Helper\DeviceSecretManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiExportCsvTrait;
use Carve\ApiBundle\Trait\ApiExportExcelTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

#[Rest\Route('/secretlog')]
#[Api\Resource(
    class: SecretLog::class,
    denyClass: SecretLogDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'secretLog:public', 'deviceType:identification', 'createdAt', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class SecretLogController extends AbstractApiController
{
    use ApiListTrait;
    use ApiExportCsvTrait;
    use ApiExportExcelTrait;
    use DeviceSecretManagerTrait;

    #[Rest\Get('/{id}/show/previous/secret', requirements: ['id' => '\d+'])]
    #[Api\Summary('Show previous secret value by {{ subjectLower }} ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Rest\View(serializerGroups: ['identification', 'secretLog:previousSecretValue'])]
    public function showPreviousAction(Request $request, int $id)
    {
        $object = $this->find($id, SecretLogDeny::SHOW_PREVIOUS);

        $this->deviceSecretManager->createUserShowPreviousSecretLog($object);

        $this->entityManager->flush();

        // Decryption
        $object->setDecryptedPreviousSecretValue($this->deviceSecretManager->getDecryptedPreviousSecretLogValue($object));

        return $object;
    }

    #[Rest\Get('/{id}/show/updated/secret', requirements: ['id' => '\d+'])]
    #[Api\Summary('Show updated secret value by {{ subjectLower }} ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Rest\View(serializerGroups: ['identification', 'secretLog:updatedSecretValue'])]
    public function showUpdatedAction(Request $request, int $id)
    {
        $object = $this->find($id, SecretLogDeny::SHOW_UPDATED);

        $this->deviceSecretManager->createUserShowUpdatedSecretLog($object);

        $this->entityManager->flush();

        // Decryption
        $object->setDecryptedUpdatedSecretValue($this->deviceSecretManager->getDecryptedUpdatedSecretLogValue($object));

        return $object;
    }
}
