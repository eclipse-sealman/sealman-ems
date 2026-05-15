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
use App\Deny\DeviceTypeHardwareDeny;
use App\Entity\DeviceTypeHardware;
use App\Form\DeviceTypeHardwareCreateType;
use App\Form\DeviceTypeHardwareEditType;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;

#[Rest\Route('/devicetypehardware')]
#[Api\Resource(
    class: DeviceTypeHardware::class,
    createFormClass: DeviceTypeHardwareCreateType::class,
    editFormClass: DeviceTypeHardwareEditType::class,
    denyClass: DeviceTypeHardwareDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'deviceTypeHardware:public', 'timestampable', 'blameable', 'deny'])]
#[IsGrantedOr('ROLE_ADMIN')]
#[Areas(['admin'])]
class DeviceTypeHardwareController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiEditTrait;
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiListTrait;
}
