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
use App\Deny\CertificateTypeDeny;
use App\Entity\CertificateType;
use App\Enum\CertificateCategory;
use App\Enum\PkiType;
use App\Form\CertificateTypeCreateType;
use App\Form\CertificateTypeEditType;
use App\Form\ScepCrlContentType;
use App\Service\Helper\PkiProvidersManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;
use Carve\ApiBundle\Exception\RequestExecutionException;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

#[Rest\Route('/certificatetype')]
#[Api\Resource(
    class: CertificateType::class,
    createFormClass: CertificateTypeCreateType::class,
    editFormClass: CertificateTypeEditType::class,
    denyClass: CertificateTypeDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'certificateType:public', 'timestampable', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class CertificateTypeController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiEditTrait;
    use ApiGetTrait;
    use ApiListTrait;
    use PkiProvidersManagerTrait;

    /**
     * All certificateTypes created by user (using this controller) have to be CUSTOM category.
     * All other categories have to be predefined - added by fixtures or migrations.
     */
    protected function getCreateObject()
    {
        $certificateType = new CertificateType();
        $certificateType->setCertificateCategory(CertificateCategory::CUSTOM);

        return $certificateType;
    }

    // manually remove all Certificate entities that don't have certificates
    protected function processDelete(object $object)
    {
        foreach ($object->getCertificates() as $certificate) {
            if (!$certificate->hasAnyCertificatePart()) {
                $this->entityManager->remove($certificate);
            } else {
                throw new RequestExecutionException('deny.certificateType.certificateTypeInUse');
            }
        }
        $this->entityManager->remove($object);
        $this->entityManager->flush();
    }

    /**
     * Added CertificateCategory::CUSTOM handling.
     */
    #[Rest\Post('/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to edit')]
    #[Api\RequestBodyEdit]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function editAction(Request $request, int $id)
    {
        $object = $this->find($id, AbstractApiObjectDeny::EDIT);

        $formOptions = [];
        if (CertificateCategory::CUSTOM !== $object->getCertificateCategory()) {
            $formOptions = [
                'predefinedCertificateCategory' => true,
            ];
        }

        return $this->handleForm($this->getEditFormClass(), $request, [$this, 'processEdit'], $object, array_merge($this->getEditFormOptions(), $formOptions));
    }

    #[Rest\Post('/scep/crl/content')]
    #[Api\Summary('Get SCEP CRL content')]
    #[Api\RequestBody(content: new NA\Model(type: ScepCrlContentType::class))]
    #[Api\Response200(description: 'CRL content', content: new OA\MediaType(mediaType: 'application/x-pkcs7-crl', schema: new OA\Schema(type: 'string')))]
    public function getScepCrlContentAction(Request $request)
    {
        $form = $this->createForm(ScepCrlContentType::class);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $url = $form->get('url')->getData();
            $verifyServerSslCertificate = $form->get('verifyServerSslCertificate')->getData();
            $scepTimeout = $form->get('scepTimeout')->getData();

            $response = $this->pkiProvidersManager->getCrlByUrl(PkiType::SCEP, $url, $scepTimeout, $verifyServerSslCertificate);

            return $response;
        }

        throw new RequestExecutionException('validation.scepCrl.invalidData');
    }
}
