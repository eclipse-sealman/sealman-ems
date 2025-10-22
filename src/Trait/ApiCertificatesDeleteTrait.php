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

trait ApiCertificatesDeleteTrait
{
    use ApiCertificateTypeHelperTrait;
    use CertificateManagerTrait;

    #[Rest\Get('/{id}/{certificateTypeId}/delete/certificate', requirements: ['id' => '\d+', 'certificateTypeId' => '\d+'])]
    #[Api\Summary('Delete {{ subjectLower }} uploaded certificate by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to delete uploaded certificate')]
    #[Api\Parameter(name: 'certificateTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of certificate type')]
    #[Api\Response204('Uploaded certificate successfully deleted')]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS')")]
    #[Areas(['admin', 'smartems'])]
    public function deleteCertificateAction(Request $request, int $id, int $certificateTypeId)
    {
        $usableCertificate = $this->getUsableCertificate($id, $certificateTypeId, CertificateDenyInterface::DELETE_CERTIFICATE);

        $this->certificateManager->handleDeleteCertificate($usableCertificate->getCertificate());
    }
}
