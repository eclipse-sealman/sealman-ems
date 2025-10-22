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
use App\Deny\UserDeny;
use App\Entity\User;
use App\Service\Helper\CertificateManagerTrait;
use App\Trait\ApiCertificatesDownloadCaTrait;
use App\Trait\ApiCertificatesDownloadCertificateTrait;
use App\Trait\ApiCertificatesDownloadPkcs12Trait;
use App\Trait\ApiCertificatesDownloadPrivateTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Service\Helper\DenyManagerTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

#[Rest\Route('/usercertificate')]
#[Api\Resource(
    class: User::class,
    denyClass: UserDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'certificate:private', 'deny'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
#[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
class UserCertificateController extends AbstractApiController
{
    use ApiCertificatesDownloadCaTrait;
    use ApiCertificatesDownloadCertificateTrait;
    use ApiCertificatesDownloadPkcs12Trait;
    use ApiCertificatesDownloadPrivateTrait;
    use DenyManagerTrait;
    use CertificateManagerTrait;

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        // User can access only his/hers certificates
        $queryBuilder->andWhere($alias.'.username = :username');
        $queryBuilder->setParameter('username', $this->getUser()->getUsername());
    }

    // Coping download certificate methods to expand security - allow VPN users to download own certificates - just in this controller

    #[Rest\Get('/{id}/{certificateTypeId}/download/ca', requirements: ['id' => '\d+', 'certificateTypeId' => '\d+'])]
    #[Api\Summary('Download CA certificate for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download CA certificate')]
    #[Api\Parameter(name: 'certificateTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of certificate type')]
    #[Api\Response200(description: 'CA certificate', content: new OA\MediaType(mediaType: 'application/x-x509-ca-cert', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
    #[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
    public function downloadCaAction(Request $request, int $id, int $certificateTypeId)
    {
        return $this->executeDownloadCaAction($request, $id, $certificateTypeId);
    }

    #[Rest\Get('/{id}/{certificateTypeId}/download/certificate', requirements: ['id' => '\d+', 'certificateTypeId' => '\d+'])]
    #[Api\Summary('Download certificate for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download certificate')]
    #[Api\Parameter(name: 'certificateTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of certificate type')]
    #[Api\Response200(description: 'Certificate', content: new OA\MediaType(mediaType: 'application/x-x509-user-cert', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
    #[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
    public function downloadCertificateAction(Request $request, int $id, int $certificateTypeId)
    {
        return $this->executeDownloadCertificateAction($request, $id, $certificateTypeId);
    }

    #[Rest\Get('/{id}/{certificateTypeId}/download/pkcs12', requirements: ['id' => '\d+', 'certificateTypeId' => '\d+'])]
    #[Api\Summary('Download PKCS#12 for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download PKCS#12')]
    #[Api\Parameter(name: 'certificateTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of certificate type')]
    #[Api\Response200(description: 'PKCS#12', content: new OA\MediaType(mediaType: 'application/x-pkcs12', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
    #[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
    public function downloadPkcs12Action(Request $request, int $id, int $certificateTypeId)
    {
        return $this->executeDownloadPkcs12Action($request, $id, $certificateTypeId);
    }

    #[Rest\Get('/{id}/{certificateTypeId}/download/private', requirements: ['id' => '\d+', 'certificateTypeId' => '\d+'])]
    #[Api\Summary('Download private key for {{ subjectLower }}')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download private key')]
    #[Api\Parameter(name: 'certificateTypeId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of certificate type')]
    #[Api\Response200(description: 'Private key', content: new OA\MediaType(mediaType: 'application/pkcs8', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
    #[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
    public function downloadPrivateAction(Request $request, int $id, int $certificateTypeId)
    {
        return $this->executeDownloadPrivateAction($request, $id, $certificateTypeId);
    }

    #[Rest\Get('/certificates')]
    #[Api\Summary('Get user certificate information')]
    #[Api\Response200Groups(description: 'Returns user certificate information', content: new NA\Model(type: User::class))]
    public function certificatesAction()
    {
        $object = $this->getUser();

        $this->fillDeny(UserDeny::class, $object);

        foreach ($object->getUseableCertificates() as $useableCertificate) {
            $useableCertificate->getCertificate()->setDecryptedCertificateCa($this->certificateManager->getCertificateCa($useableCertificate->getCertificate()));
            $useableCertificate->getCertificate()->setDecryptedCertificate($this->certificateManager->getCertificate($useableCertificate->getCertificate()));
            $useableCertificate->getCertificate()->setDecryptedPrivateKey($this->certificateManager->getPrivateKey($useableCertificate->getCertificate()));
        }

        return $object;
    }
}
