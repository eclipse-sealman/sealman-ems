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
use App\Entity\DeviceMTlsAuthentication;
use App\Form\DeviceMTlsAuthenticationType;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Helper\Arr;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Rest\Route('/devicemtlsauthentication')]
#[Api\Resource(
    class: DeviceMTlsAuthentication::class,
    createFormClass: DeviceMTlsAuthenticationType::class,
    editFormClass: DeviceMTlsAuthenticationType::class,
    listFormFilterByAppend: ['deviceTypes.id'],
    // Subject will be lowercased anyway (mTLS will became mtls). Not worth the effort for now.
    subject: 'Device mTLS authentication',
)]
#[Rest\View(serializerGroups: ['identification', 'deviceMTlsAuthentication:public', 'timestampable', 'blameable'])]
#[IsGranted('ROLE_ADMIN')]
#[Areas(['admin'])]
class DeviceMTlsAuthenticationController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiEditTrait;
    use ApiGetTrait;
    use ApiListTrait;

    protected function processCreate($deviceMTlsAuthentication, FormInterface $form)
    {
        return $this->processDeviceMTlsAuthentication($deviceMTlsAuthentication);
    }

    protected function processEdit($deviceMTlsAuthentication, FormInterface $form)
    {
        return $this->processDeviceMTlsAuthentication($deviceMTlsAuthentication);
    }

    protected function processDeviceMTlsAuthentication($deviceMTlsAuthentication)
    {
        try {
            $certificateArray = \openssl_x509_parse($deviceMTlsAuthentication->getCertificateCa());
            $deviceMTlsAuthentication->setCertificateCaSubject(Arr::get($certificateArray, 'subject.CN', 'unknown'));

            $validTo = Arr::get($certificateArray, 'validTo_time_t');
            if ($validTo) {
                $deviceMTlsAuthentication->setCertificateCaValidTo(new \DateTime("@$validTo"));
            }
        } catch (\Throwable $e) {
            // Ignore parsing errors here, validation should have caught them
        }

        $this->entityManager->persist($deviceMTlsAuthentication);
        $this->entityManager->flush();

        $this->modifyResponseObject($deviceMTlsAuthentication);

        return $deviceMTlsAuthentication;
    }
}
