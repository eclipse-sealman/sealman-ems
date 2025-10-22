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
use App\Entity\Label;
use App\Form\LabelType;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

#[Rest\Route('/label')]
#[Api\Resource(
    class: Label::class,
    createFormClass: LabelType::class,
    editFormClass: LabelType::class
)]
#[Rest\View(serializerGroups: ['identification', 'label:public'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class LabelController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiEditTrait;
    use ApiGetTrait;
    use ApiListTrait;
}
