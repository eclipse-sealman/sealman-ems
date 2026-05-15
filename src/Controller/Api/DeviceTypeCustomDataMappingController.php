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
use App\Entity\DeviceTypeCustomDataMapping;
use App\Form\DeviceTypeCustomDataMappingCreateType;
use App\Form\DeviceTypeCustomDataMappingEditType;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Rest\Route('/devicetypecustomdatamappings')]
#[Api\Resource(
    class: DeviceTypeCustomDataMapping::class,
    createFormClass: DeviceTypeCustomDataMappingCreateType::class,
    editFormClass: DeviceTypeCustomDataMappingEditType::class
)]
#[Rest\View(serializerGroups: ['identification', 'deviceTypeCustomDataMapping:public', 'customData:public', 'deviceType:identification', 'timestampable', 'blameable', 'deny'])]
#[IsGranted('ROLE_ADMIN')]
#[Areas(['admin'])]
class DeviceTypeCustomDataMappingController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiEditTrait;
    use ApiGetTrait;
    use ApiListTrait;
}
