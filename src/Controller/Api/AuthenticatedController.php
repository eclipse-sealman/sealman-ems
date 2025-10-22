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
use App\Form\AuthenticatedChangePasswordType;
use App\Service\Helper\PasswordManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Rest\Route('/authenticated')]
#[Rest\View(serializerGroups: ['public'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
#[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
#[OA\Tag('Authenticated')]
class AuthenticatedController extends AbstractFOSRestController
{
    use PasswordManagerTrait;

    #[Rest\Post('/change/password')]
    #[Api\Summary('Change authenticated user password')]
    #[Api\RequestBody(content: new NA\Model(type: AuthenticatedChangePasswordType::class))]
    #[Api\Response204('Password successfully changed')]
    #[Api\Response400]
    public function changePasswordAction(Request $request)
    {
        $user = $this->getUser();

        if ($user->getRadiusUser()) {
            throw new AccessDeniedHttpException();
        }

        if ($user->getSsoUser()) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(AuthenticatedChangePasswordType::class, null, ['user' => $user]);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPlainPassword = $form->get('newPlainPassword')->getData();

            $this->passwordManager->changePassword($user, $newPlainPassword);

            return;
        }

        return $form;
    }
}
