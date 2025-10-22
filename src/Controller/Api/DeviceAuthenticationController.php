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
use App\Entity\User;
use App\Entity\UserDeviceType;
use App\Enum\UserRole;
use App\Form\DeviceAuthenticationType;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Service\Attribute\Required;

#[Rest\Route('/deviceauthentication')]
#[Api\Resource(
    tag: 'DeviceAuthentication',
    subject: 'device authentication',
    class: User::class,
    createFormClass: DeviceAuthenticationType::class,
    editFormClass: DeviceAuthenticationType::class,
    listFormFilterByAppend: ['userDeviceTypes.deviceType']
)]
#[Rest\View(serializerGroups: ['identification', 'deviceAuthentication:public', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class DeviceAuthenticationController extends AbstractApiController
{
    use ApiGetTrait;
    use ApiCreateTrait;
    use ApiEditTrait;
    use ApiDeleteTrait;
    use ApiListTrait;

    /**
     * @var UserPasswordHasherInterface
     */
    protected $userPasswordHasher;

    #[Required]
    public function setUserPasswordHasher(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * @var PasswordHasherFactoryInterface
     */
    protected $passwordHasherFactory;

    #[Required]
    public function setPasswordHasherFactory(PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $queryBuilder->andWhere($alias.'.roleDevice = :roleDevice AND '.$alias.'.roleSystem = :roleSystem');
        $queryBuilder->setParameter('roleDevice', true);
        $queryBuilder->setParameter('roleSystem', false);
    }

    protected function modifyResponseObject(object $object): void
    {
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($object);
        if ($passwordHasher && \method_exists($passwordHasher, 'unHash')) {
            $object->setDecryptedPassword($passwordHasher->unHash($object->getPassword(), $object->getSalt()));
        } else {
            $object->setDecryptedPassword('');
        }
    }

    protected function getCreateObject()
    {
        $user = new User();
        $user->setRoleDevice(true);

        return $user;
    }

    protected function processCreate($user, FormInterface $form)
    {
        return $this->processDeviceAuthenticationUser($user);
    }

    protected function processEdit($user, FormInterface $form)
    {
        return $this->processDeviceAuthenticationUser($user);
    }

    protected function processDeviceAuthenticationUser($user)
    {
        $saltLength = 64;
        try {
            $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);
            // Method might not exist if custom AesPasswordHasher is not used - this should not happen
            $saltLength = $passwordHasher->getRequiredSaltLength();
        } catch (\Throwable $e) {
        }

        $user->setSalt(base64_encode(openssl_random_pseudo_bytes($saltLength)));

        $hashedPassword = $this->userPasswordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);

        foreach ($user->getUserDeviceTypes() as $userDeviceType) {
            if (!$user->getDeviceTypes()->contains($userDeviceType->getDeviceType())) {
                $user->removeUserDeviceType($userDeviceType);
                $this->entityManager->remove($userDeviceType);
            }
        }

        foreach ($user->getDeviceTypes() as $deviceType) {
            $contains = false;
            foreach ($user->getUserDeviceTypes() as $userDeviceType) {
                if ($userDeviceType->getDeviceType() == $deviceType) {
                    $contains = true;
                }
            }
            if (!$contains) {
                $userDeviceType = new UserDeviceType();
                $userDeviceType->setUser($user);
                $userDeviceType->setDeviceType($deviceType);
                $userDeviceType->setUserRole(UserRole::DEVICE);
                $user->addUserDeviceType($userDeviceType);
                $this->entityManager->persist($userDeviceType);
            }
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->modifyResponseObject($user);

        return $user;
    }
}
