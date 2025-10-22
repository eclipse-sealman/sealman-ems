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
use App\Entity\OpenSourceLicense;
use App\Service\Helper\OpenSourceLicenseManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiExportCsvTrait;
use Carve\ApiBundle\Trait\ApiExportExcelTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/opensourcelicense')]
#[Api\Resource(
    class: OpenSourceLicense::class
)]
#[Rest\View(serializerGroups: ['identification', 'openSourceLicense:public', 'timestampable'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class OpenSourceLicenseController extends AbstractApiController
{
    use ApiListTrait;
    use ApiExportCsvTrait;
    use ApiExportExcelTrait;
    use OpenSourceLicenseManagerTrait;

    #[Rest\Get('/download/txt')]
    #[Api\Summary('Download open source licenses as TXT')]
    #[Api\Response200(description: 'Open source licenses', content: new OA\MediaType(mediaType: 'text/plain', schema: new OA\Schema(type: 'string')))]
    public function downloadTextAction()
    {
        $fullPath = $this->openSourceLicenseManager->getLicensesTxtFile();
        $fs = new Filesystem();

        if (!$fs->exists($fullPath)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($fullPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($fullPath));

        return $response;
    }
}
