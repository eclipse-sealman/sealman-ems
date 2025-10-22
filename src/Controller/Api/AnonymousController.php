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
use App\Model\PublicConfiguration;
use App\Service\Helper\ConfigurationManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;

#[Rest\Route('/anonymous')]
#[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
#[OA\Tag('Anonymous')]
class AnonymousController extends AbstractApiController
{
    use ConfigurationManagerTrait;

    #[Rest\Get('/configuration')]
    #[Rest\View(serializerGroups: ['public:configuration'])]
    #[Api\Summary('Get public configuration for application')]
    #[Api\Response200Groups(description: 'Returns public configuration for application', content: new NA\Model(type: PublicConfiguration::class))]
    public function getAction()
    {
        return $this->configurationManager->getPublicConfiguration();
    }
}
