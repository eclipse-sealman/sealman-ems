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
use App\Model\DiskStatusModel;
use App\Model\SystemStatusModel;
use App\Service\Helper\SystemStatusManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/status')]
#[Rest\View(serializerGroups: ['status:public'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
#[OA\Tag('Status')]
class StatusController extends AbstractApiController
{
    use SystemStatusManagerTrait;

    #[Rest\Get('/disk')]
    #[Api\Summary('Get disk status')]
    #[Api\Response200Groups(description: 'Disk status', content: new NA\Model(type: DiskStatusModel::class))]
    public function diskStatusAction()
    {
        return $this->systemStatusManager->getDiskStatus();
    }

    #[Rest\Get('/system')]
    #[Api\Summary('Get system status')]
    #[Api\Response200Groups(description: 'System status', content: new NA\Model(type: SystemStatusModel::class))]
    public function systemStatusAction()
    {
        return $this->systemStatusManager->getSystemStatus();
    }
}
