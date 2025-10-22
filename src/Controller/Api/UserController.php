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
use App\Entity\CertificateType;
use App\Entity\User;
use App\Enum\CertificateEntity;
use App\Form\UserChangePasswordType;
use App\Form\UserCreateType;
use App\Form\UserDisableType;
use App\Form\UserEditType;
use App\Form\UserEnableType;
use App\Model\UseableCertificate;
use App\Service\Helper\AuthenticationManagerTrait;
use App\Service\Helper\EventDispatcherTrait;
use App\Service\Helper\VpnManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use App\Trait\ApiCertificatesAllActionsTrait;
use App\Trait\ApiVpnDownloadConfigurationTrait;
use Carve\ApiBundle\Attribute\AddRoleBasedSerializerGroups;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Exception\RequestExecutionException;
use Carve\ApiBundle\Model\ListQueryFilterInterface;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Service\Attribute\Required;

#[Rest\Route('/user')]
#[Api\Resource(
    class: User::class,
    createFormClass: UserCreateType::class,
    editFormClass: UserEditType::class,
    denyClass: UserDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'user:public', 'certificate:admin', 'deny'])]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN_VPN', ['user:adminVpn'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class UserController extends AbstractApiController
{
    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiEditTrait;
    use ApiGetTrait;
    use ApiListTrait;
    use AuthenticationManagerTrait;
    use ApiVpnDownloadConfigurationTrait;
    use ApiCertificatesAllActionsTrait;
    use CertificateTypeHelperTrait;
    use EventDispatcherTrait;
    use VpnManagerTrait;

    /**
     * @var UserPasswordHasherInterface
     */
    protected $userPasswordHasher;

    #[Required]
    public function setUserPasswordHasher(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $conditions = [
            $alias.'.roleAdmin = :role',
            $alias.'.roleSmartems = :role',
        ];

        if ($this->isGranted('ROLE_ADMIN_VPN')) {
            $conditions[] = $alias.'.roleVpn = :role';
        }

        $queryBuilder->andWhere(implode(' OR ', $conditions));
        $queryBuilder->setParameter('role', true);
    }

    protected function modifyFilter(ListQueryFilterInterface $filter, QueryBuilder $queryBuilder, string $alias): bool
    {
        $filterBy = $filter->getFilterBy();
        if ('enabled' === $filterBy) {
            $filterValue = $filter->getFilterValue();
            if ($filterValue) {
                $queryBuilder->andWhere('('.$alias.'.enabled = :enabled AND ('.$alias.'.enabledExpireAt >= :enabledExpireAtNow OR '.$alias.'.enabledExpireAt IS NULL))');
            } else {
                $queryBuilder->andWhere('('.$alias.'.enabled = :enabled OR ('.$alias.'.enabledExpireAt < :enabledExpireAtNow AND '.$alias.'.enabledExpireAt IS NOT NULL))');
            }

            $queryBuilder->setParameter('enabled', $filterValue);
            $queryBuilder->setParameter('enabledExpireAtNow', new \DateTime());

            return true;
        }

        if ('username' === $filterBy) {
            $filterValue = $filter->getFilterValue();

            $queryBuilder->andWhere('('.$alias.'.username LIKE :username AND '.$alias.'.ssoUser = 0) OR ('.$alias.'.ssoName LIKE :username AND '.$alias.'.ssoUser = 1)');
            $queryBuilder->setParameter('username', '%'.$filterValue.'%');

            return true;
        }

        return false;
    }

    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        if ('username' === $sorting->getField()) {
            $queryBuilder->addOrderBy('CASE WHEN '.$alias.'.ssoUser = 0 THEN '.$alias.'.username ELSE '.$alias.'.ssoName END', $sorting->getDirection()->value);

            return true;
        }

        return false;
    }

    protected function processCreate($object, FormInterface $form)
    {
        $plainPassword = $form->get('plainPassword')->getData();

        $hashedPassword = $this->userPasswordHasher->hashPassword($object, $plainPassword);
        $object->setPassword($hashedPassword);

        if ($object->getRoleAdmin()) {
            // making sure that entity data is consistent
            $object->setRoleSmartems(false);
            $object->setRoleVpn(false);
            $object->setAccessTags(new ArrayCollection());
        }

        // persist object before  which can result in RequestExecutionException
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        // Filling deny to have correctly set $useableCertificates for automatic behaviours
        if ($this->hasDenyClass()) {
            $denyClass = $this->getDenyClass();
            $this->fillDeny($denyClass, $object);
        }

        $this->dispatchUserUpdated($object);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }

    protected function processEdit($object, FormInterface $form)
    {
        if ($object->getRoleAdmin()) {
            // making sure that entity data is consistent
            $object->setRoleSmartems(false);
            $object->setRoleVpn(false);
            $object->setAccessTags(new ArrayCollection());
        }

        // persist object before dispatchUserUpdated which can result in RequestExecutionException
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->dispatchUserUpdated($object);
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        return $object;
    }

    protected function processDelete(object $object)
    {
        $this->dispatchUserPreRemove($object);

        $this->entityManager->remove($object);
        $this->entityManager->flush();
    }

    #[Rest\Post('/changepassword/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Change {{ subjectLower }} password by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to change password')]
    #[Api\RequestBody(content: new NA\Model(type: UserChangePasswordType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    #[Api\Response404Id]
    public function changePasswordAction(Request $request, int $id)
    {
        $object = $this->find($id, UserDeny::CHANGE_PASSWORD);

        return $this->handleForm(UserChangePasswordType::class, $request, function ($data, FormInterface $form) use ($object) {
            $plainPassword = $form->get('newPlainPassword')->getData();

            $hashedPassword = $this->userPasswordHasher->hashPassword($object, $plainPassword);
            $object->setPassword($hashedPassword);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            return $object;
        }, null, ['user' => $object]);
    }

    #[Rest\Get('/resettotpsecret/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Reset {{ subjectLower }} TOTP secret by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to reset TOTP secret')]
    #[Api\Response204('TOTP secret reset successfully')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function resetTotpSecretAction(Request $request, int $id)
    {
        $object = $this->find($id, UserDeny::RESET_TOTP_SECRET);

        $object->setTotpSecret(null);
        $this->entityManager->persist($object);
        $this->entityManager->flush();
    }

    #[Rest\Get('/resetloginattempts/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Reset {{ subjectLower }} login attempts by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to reset login attempts')]
    #[Api\Response204('Login attempts reset successfully')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function resetLoginAttemptsAction(Request $request, int $id)
    {
        $object = $this->find($id, UserDeny::RESET_LOGIN_ATTEMPTS);

        // Note: resetLoginAttempts function does not execute entityManager->flush()
        $this->authenticationManager->resetLoginAttempts($object);
        $this->entityManager->flush();
    }

    #[Rest\Post('/disable/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Disable {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to disable')]
    #[Api\RequestBody(content: new NA\Model(type: UserDisableType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    #[Api\Response404Id]
    public function disableAction(Request $request, int $id)
    {
        $object = $this->find($id, UserDeny::DISABLE);

        // We need to pre-set this to allow proper checks using validators
        $object->setEnabled(false);

        return $this->handleForm(UserDisableType::class, $request, function ($object) {
            $object->setEnabledExpireAt(null);

            // persist object before dispatchUserUpdated which can result in RequestExecutionException
            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $this->dispatchUserUpdated($object);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            return $object;
        }, $object);
    }

    #[Rest\Post('/enable/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Enable {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to enable')]
    #[Api\RequestBody(content: new NA\Model(type: UserEnableType::class))]
    #[Api\Response200SubjectGroups]
    #[Api\Response400]
    #[Api\Response404Id]
    public function enableAction(Request $request, int $id)
    {
        $object = $this->find($id, UserDeny::ENABLE);

        // We need to pre-set this to allow proper checks using validators
        $object->setEnabled(true);

        return $this->handleForm(UserEnableType::class, $request, function ($object) {
            // persist object before dispatchUserUpdated which can result in RequestExecutionException
            $this->entityManager->persist($object);
            $this->entityManager->flush();

            $this->dispatchUserUpdated($object);

            $this->entityManager->persist($object);
            $this->entityManager->flush();

            return $object;
        }, $object);
    }

    // Method provides array of available certificate types for all users (used in batch enable/disable)
    #[Rest\Get('/certificate/types')]
    #[Api\Summary('Get list of available certificate types for {{ subjectPluralLower }}')]
    #[Api\Response200ArraySubjectGroups(CertificateType::class)]
    #[Security("is_granted('ROLE_ADMIN_SCEP')")]
    #[Areas(['admin:scep'])]
    public function getCertificateTypesAction()
    {
        return $this->getAvailableCertificateTypesForCertificateEntity(CertificateEntity::USER);
    }

    // Method provides initial data for creating user with useable certificate data
    #[Rest\Get('/initial/useable/certificates')]
    #[Api\Summary('List of {{ subjectLower }} useable certificates')]
    #[Api\Response200SubjectGroups]
    public function useableCertificatesAction(Request $request)
    {
        $user = new User();

        $useableCertificates = new ArrayCollection();

        foreach ($this->getAvailableCertificateTypes(new User()) as $certificateType) {
            $useableCertificate = new UseableCertificate();
            $useableCertificate->setCertificateType($certificateType);
            $useableCertificates->add($useableCertificate);
        }

        $user->setUseableCertificates($useableCertificates);

        return $user;
    }

    // TechnicianVPN certificate type actions wrapped into backwards compatible endpoint
    #[Rest\Get('/{id}/generate/certificate', requirements: ['id' => '\d+'])]
    #[Api\Summary('Generate technician VPN certificate for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to generate technician VPN certificate')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN_SCEP')")]
    #[Areas(['admin:scep'])]
    public function generateTechnicianVpnCertificateAction(Request $request, int $id)
    {
        $technicianVpnCertificateType = $this->getTechnicianVpnCertificateType();
        if (!$technicianVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->generateCertificateAction($request, $id, $technicianVpnCertificateType->getId());
    }

    #[Rest\Get('/{id}/revoke/certificate', requirements: ['id' => '\d+'])]
    #[Api\Summary('Revoke technician VPN certificate for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to revoke technician VPN certificate')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN_SCEP')")]
    #[Areas(['admin:scep'])]
    public function revokeTechnicianVpnCertificateAction(Request $request, int $id)
    {
        $technicianVpnCertificateType = $this->getTechnicianVpnCertificateType();
        if (!$technicianVpnCertificateType) {
            throw new NotFoundHttpException();
        }

        return $this->revokeCertificateAction($request, $id, $technicianVpnCertificateType->getId());
    }
}
