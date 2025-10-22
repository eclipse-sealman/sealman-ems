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

namespace App\Trait;

use App\Attribute\Areas;
use App\Deny\CertificateDenyInterface;
use App\Form\CertificateUploadFilesType;
use App\Model\CertificateUploadFilesModel;
use App\Service\Helper\CertificateManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

trait ApiCertificatesUploadFilesTrait
{
    use CertificateManagerTrait;

    use ApiCertificateTypeHelperTrait;

    #[Rest\Post('/{id}/{certificateTypeId}/upload/files', requirements: ['id' => '\d+', 'certificateTypeId' => '\d+'])]
    #[Api\Summary('Upload CA certificate, certificate and private key for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to upload CA certificate, certificate and private key')]
    #[Api\Parameter(name: 'certificateTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of certificate type')]
    #[Api\RequestBody(content: new NA\Model(type: CertificateUploadFilesType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function uploadFilesAction(Request $request, int $id, int $certificateTypeId)
    {
        $usableCertificate = $this->getUsableCertificate($id, $certificateTypeId, CertificateDenyInterface::UPLOAD_CERTIFICATES);

        $model = new CertificateUploadFilesModel();
        $model->setCertificateObject($usableCertificate->getCertificate());

        return $this->handleForm(CertificateUploadFilesType::class, $request, function ($model) {
            $result = $this->certificateManager->handleUploadCertificateFiles($model);

            if (true !== $result) {
                throw new RequestExecutionException($result);
            }

            $this->modifyResponseObject($this->getApiCertificateObject());

            return $this->getApiCertificateObject();
        }, $model);
    }
}
