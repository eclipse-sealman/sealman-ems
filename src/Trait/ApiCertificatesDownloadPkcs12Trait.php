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
use App\Service\Helper\CertificateManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Exception\RequestExecutionException;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ApiCertificatesDownloadPkcs12Trait
{
    use ApiCertificateTypeHelperTrait;
    use CertificateManagerTrait;

    #[Rest\Get('/{id}/{certificateTypeId}/download/pkcs12', requirements: ['id' => '\d+', 'certificateTypeId' => '\d+'])]
    #[Api\Summary('Download PKCS#12 for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download PKCS#12')]
    #[Api\Parameter(name: 'certificateTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of certificate type')]
    #[Api\Response200(description: 'PKCS#12', content: new OA\MediaType(mediaType: 'application/x-pkcs12', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function downloadPkcs12Action(Request $request, int $id, int $certificateTypeId)
    {
        return $this->executeDownloadPkcs12Action($request, $id, $certificateTypeId);
    }

    public function executeDownloadPkcs12Action(Request $request, int $id, int $certificateTypeId)
    {
        $usableCertificate = $this->getUsableCertificate($id, $certificateTypeId, CertificateDenyInterface::DOWNLOAD_PKCS12);

        $filename = 'certificate';
        if ($usableCertificate->getCertificate()->getCertificateSubject()) {
            $filename = $usableCertificate->getCertificate()->getCertificateSubject();
        }

        $filename .= '.p12';
        $content = $this->certificateManager->getPkcs12($usableCertificate->getCertificate());

        if (!$content) {
            throw new RequestExecutionException('error.certificate.noPkcs12');
        }

        $response = new Response($content);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/x-pkcs12');

        return $response;
    }
}
