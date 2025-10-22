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
use App\Entity\DeviceFailedLoginAttempt;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/devicefailedloginattempt')]
#[Api\Resource(
    class: DeviceFailedLoginAttempt::class
)]
#[Rest\View(serializerGroups: ['identification', 'deviceFailedLoginAttempt:public', 'createdAt'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class DeviceFailedLoginAttemptController extends AbstractApiController
{
    use ApiListTrait;
}
