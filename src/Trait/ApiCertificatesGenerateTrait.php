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
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

trait ApiCertificatesGenerateTrait
{
    use ApiCertificateTypeHelperTrait;
    use CertificateManagerTrait;

    #[Rest\Get('/{id}/{certificateTypeId}/generate/certificate', requirements: ['id' => '\d+', 'certificateTypeId' => '\d+'])]
    #[Api\Summary('Generate certificate for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to generate certificate')]
    #[Api\Parameter(name: 'certificateTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of certificate type')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN_SCEP')")]
    #[Areas(['admin:scep'])]
    public function generateCertificateAction(Request $request, int $id, int $certificateTypeId)
    {
        $usableCertificate = $this->getUsableCertificate($id, $certificateTypeId, CertificateDenyInterface::GENERATE_CERTIFICATE);

        $this->certificateManager->generateCertificate($usableCertificate->getCertificate());

        $this->modifyResponseObject($this->getApiCertificateObject());

        return $this->getApiCertificateObject();
    }
}
