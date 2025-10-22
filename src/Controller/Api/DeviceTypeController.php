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
use App\Deny\DeviceTypeDeny;
use App\Entity\Certificate;
use App\Entity\CertificateType;
use App\Entity\Device;
use App\Entity\DeviceType;
use App\Enum\AuthenticationMethod;
use App\Enum\CertificateEntity;
use App\Enum\CommunicationProcedure;
use App\Enum\CommunicationProcedureRequirement;
use App\Enum\CredentialsSource;
use App\Form\DeviceTypeLimitedType;
use App\Form\DeviceTypeType;
use App\Service\Helper\AuditableManagerTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use App\Trait\ApiDuplicateTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;
use Carve\ApiBundle\Trait\ApiCreateTrait;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/devicetype')]
#[Api\Resource(
    class: DeviceType::class,
    createFormClass: DeviceTypeType::class,
    editFormClass: DeviceTypeType::class,
    denyClass: DeviceTypeDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'deviceType:public', 'deny'])]
#[Security("is_granted('ROLE_ADMIN')")]
#[Areas(['admin'])]
class DeviceTypeController extends AbstractApiController
{
    use DeviceCommunicationFactoryTrait;
    use CertificateTypeHelperTrait;
    use AuditableManagerTrait;

    use ApiCreateTrait;
    use ApiDeleteTrait;
    use ApiEditTrait;
    use ApiGetTrait;
    use ApiDuplicateTrait;
    use ApiListTrait;

    #[Rest\Get('/{id}/enable', requirements: ['id' => '\d+'])]
    #[Api\Summary('Enable {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to enable')]
    #[Api\Response200SubjectGroups('Returns {{ subjectLower }}')]
    #[Api\Response404Id]
    public function enableAction(Request $request, int $id)
    {
        $object = $this->find($id, DeviceTypeDeny::ENABLE);
        $object->setEnabled(true);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    #[Rest\Get('/{id}/disable', requirements: ['id' => '\d+'])]
    #[Api\Summary('Disable {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to disable')]
    #[Api\Response200SubjectGroups('Returns {{ subjectLower }}')]
    #[Api\Response404Id]
    public function disableAction(Request $request, int $id)
    {
        $object = $this->find($id, DeviceTypeDeny::DISABLE);
        $object->setEnabled(false);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    #[Rest\Post('/create')]
    #[Api\Summary('Create {{ subjectLower }}')]
    #[Api\RequestBodyCreate]
    #[Api\Response200SubjectGroups('Returns created {{ subjectLower }}')]
    #[Api\Response400]
    public function createAction(Request $request)
    {
        return $this->handleForm($this->getCreateFormClass(), $request, [$this, 'processCreate'], $this->getCreateObject(), $this->getCreateFormOptions());
    }

    protected function processCreate($object, FormInterface $form)
    {
        $this->processAuthenticationMethodCredentials($object);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    #[Rest\Post('/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Edit {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to edit')]
    #[Api\RequestBodyEdit]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function editAction(Request $request, int $id)
    {
        $object = $this->find($id, DeviceTypeDeny::EDIT);

        $previousCertificateTypes = new ArrayCollection();

        foreach ($object->getCertificateTypes() as $certificateType) {
            $previousCertificateTypes->add(clone $certificateType);
        }

        return $this->handleForm($this->getEditFormClass(), $request, function (DeviceType $object, FormInterface $form) use ($previousCertificateTypes) {
            $this->processSubjectAlt($object, $previousCertificateTypes);
            $this->processAuthenticationMethodCredentials($object);

            return $this->processEdit($object, $form);
        }, $object, $this->getEditFormOptions());
    }

    #[Rest\Post('/limitededit/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Limited edit {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to limited edit')]
    #[Api\RequestBody(content: new NA\Model(type: DeviceTypeLimitedType::class))]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function limitedEditAction(Request $request, int $id)
    {
        $object = $this->find($id, DeviceTypeDeny::LIMITED_EDIT);

        $hasNoneCommunicationProcedure = false;
        if (
            in_array(
                $object->getCommunicationProcedure(),
                [
                    CommunicationProcedure::NONE,
                    CommunicationProcedure::NONE_SCEP,
                    CommunicationProcedure::NONE_VPN,
                ]
            )) {
            $hasNoneCommunicationProcedure = true;
        }

        // CertificateType cannot be deleted if at one device has certificate in this certificateType
        $requiredCertificateTypes = $this->getUsedCertificateTypes($object);

        $limitedEditOptions = [
            'hasCertificates' => $object->getHasCertificates(),
            'hasVpn' => $object->getHasVpn(),
            'hasEndpointDevices' => $object->getHasEndpointDevices(),
            'hasMasquerade' => $object->getHasMasquerade(),
            'hasDeviceCommands' => $object->getHasDeviceCommands(),
            'hasConfig' => $object->getHasConfig1() || $object->getHasConfig2() || $object->getHasConfig3(),
            'hasFirmware' => $object->getHasFirmware1() || $object->getHasFirmware2() || $object->getHasFirmware3(),
            'hasNoneCommunicationProcedure' => $hasNoneCommunicationProcedure,
            'requiredCertificateTypes' => $requiredCertificateTypes,
        ];

        $previousCertificateTypes = new ArrayCollection();

        foreach ($object->getCertificateTypes() as $certificateType) {
            $previousCertificateTypes->add(clone $certificateType);
        }

        return $this->handleForm(DeviceTypeLimitedType::class, $request, function (DeviceType $object, FormInterface $form) use ($previousCertificateTypes) {
            $this->processSubjectAlt($object, $previousCertificateTypes);
            $this->processAuthenticationMethodCredentials($object);

            return $this->processEdit($object, $form);
        }, $object, array_merge($this->getEditFormOptions(), $limitedEditOptions));
    }

    protected function processAuthenticationMethodCredentials(DeviceType $object)
    {
        switch ($object->getAuthenticationMethod()) {
            case AuthenticationMethod::BASIC:
            case AuthenticationMethod::DIGEST:
                if (CredentialsSource::USER == $object->getCredentialsSource()) {
                    $object->setDeviceTypeSecretCredential(null);
                }
                $object->setDeviceTypeCertificateTypeCredential(null);
                break;
            case AuthenticationMethod::X509:
                $object->setDeviceTypeSecretCredential(null);
                $object->setCredentialsSource(null);
                break;
            case AuthenticationMethod::NONE:
            default:
                $object->setDeviceTypeCertificateTypeCredential(null);
                $object->setDeviceTypeSecretCredential(null);
                $object->setCredentialsSource(null);
                break;
        }
    }

    protected function processSubjectAlt(DeviceType $object, Collection $previousCertificateTypes)
    {
        $certificateTypes = $object->getCertificateTypes();
        foreach ($previousCertificateTypes as $previousCertificateType) {
            $certificateType = null;
            foreach ($certificateTypes as $loopCertificateType) {
                if ($loopCertificateType->getCertificateType() == $previousCertificateType->getCertificateType()) {
                    $certificateType = $loopCertificateType;
                    break;
                }
            }

            if (!$certificateType) {
                continue;
            }

            if ($certificateType->getEnableSubjectAltName() == $previousCertificateType->getEnableSubjectAltName() &&
                $certificateType->getSubjectAltNameType() == $previousCertificateType->getSubjectAltNameType() &&
                $certificateType->getSubjectAltNameValue() == $previousCertificateType->getSubjectAltNameValue()
            ) {
                continue;
            }

            $connection = $this->entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $queryBuilder = $this->entityManager->getRepository(Certificate::class)->createQueryBuilder('c');
                $queryBuilder->update(Certificate::class, 'c');
                $queryBuilder->set('c.revokeCertificateOnNextCommunication', true);
                $queryBuilder->andWhere('c.device IN (SELECT d.id FROM '.Device::class.' d WHERE d.deviceType = :deviceType)');
                $queryBuilder->setParameter('deviceType', $object);
                $queryBuilder->andWhere('c.certificateType = :certificateType');
                $queryBuilder->setParameter('certificateType', $certificateType->getCertificateType());
                $queryBuilder->andWhere('c.certificateGenerated = :certificateGenerated');
                $queryBuilder->setParameter('certificateGenerated', true);
                $queryBuilder->andWhere('c.revokeCertificateOnNextCommunication = :revokeCertificateOnNextCommunication');
                $queryBuilder->setParameter('revokeCertificateOnNextCommunication', false);

                $this->auditableManager->createPartialBatchUpdate($queryBuilder, ['revokeCertificateOnNextCommunication' => false], ['revokeCertificateOnNextCommunication' => true]);

                $queryBuilder->getQuery()->execute();

                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }
    }

    #[Rest\Get('/used/certificate/types/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Lists certificate types that are used (at least one device has certificate in this certificate type) in {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to list used cerificate types by this {{ subjectLower }}')]
    #[Api\Response200ArraySubjectGroups(CertificateType::class)]
    public function usedCertificateTypesAction(Request $request, int $id)
    {
        $object = $this->find($id, AbstractApiObjectDeny::GET);

        $list = $this->getUsedCertificateTypes($object);

        return $list;
    }

    /**
     * Method lists certificateTypes that are used in provided deviceType
     * If certificateType is in resulting array|Collection means there is at least one device of provided deviceType with certificate in this certificateType
     * Any certificateType in resulting list cannot be removed from deviceType.
     */
    protected function getUsedCertificateTypes(DeviceType $object): array|Collection
    {
        return $this->entityManager->getRepository(CertificateType::class)
                ->createQueryBuilder('ct')
                ->leftJoin('ct.certificates', 'c')
                ->leftJoin('c.device', 'd')
                ->andWhere('d.deviceType = :deviceType')
                ->setParameter('deviceType', $object)
                ->andWhere('c.certificate IS NOT NULL OR c.certificateCa IS NOT NULL OR c.privateKey IS NOT NULL')
                ->distinct()
                ->getQuery()
                ->getResult();
    }

    protected function processDuplicate($object)
    {
        $duplicatedObject = $this->getDuplicatedObject($object);
        $duplicatedObject->setName($this->getUniqueCopiedString($object, 'name'));
        $duplicatedObject->setSlug($this->getUniqueCopiedString($object, 'slug', ''));
        $duplicatedObject->setCertificateCommonNamePrefix($this->getUniqueCopiedString($object, 'certificateCommonNamePrefix', '', 6));

        if ('/' == $object->getRoutePrefix()) {
            $object->setRoutePrefix('/duplicate');
        }
        $duplicatedObject->setRoutePrefix($this->getUniqueCopiedString($object, 'routePrefix', ''));

        // Credentials cannot be duplicated since they are specific for one device type
        $duplicatedObject->setDeviceTypeSecretCredential(null);
        $duplicatedObject->setDeviceTypeCertificateTypeCredential(null);

        $this->duplicateCollection($object, $duplicatedObject, 'certificateTypes');

        $this->entityManager->persist($duplicatedObject);
        $this->entityManager->flush();

        return $duplicatedObject;
    }

    #[Rest\Get('/default/{communicationProcedureName}')]
    #[Api\Summary('Get default values for {{ subjectLower }} with communication procedure')]
    #[Api\Parameter(name: 'communicationProcedureName', in: 'path', description: 'Communication procedure name')]
    #[Api\Response200SubjectGroups('Returns default values for {{ subjectLower }} with communication procedure')]
    #[Api\Response404('Communication procedure with specified name was not found')]
    public function getDefaultDeviceTypeValues(string $communicationProcedureName)
    {
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByName($communicationProcedureName);

        if (!$communicationProcedure) {
            throw new NotFoundHttpException();
        }

        $deviceType = new DeviceType();

        foreach (CommunicationProcedureRequirement::cases() as $requirement) {
            $functionName = 'set'.ucfirst($requirement->value);
            if (in_array($requirement, $communicationProcedure->getCommunicationProcedureRequirementsRequired())) {
                // Required field set true
                $deviceType->$functionName(true);
            } elseif (!in_array($requirement, $communicationProcedure->getCommunicationProcedureRequirementsOptional())) {
                // Unused field set false
                $deviceType->$functionName(false);
            }
        }

        foreach (CommunicationProcedure::cases() as $availableCommunicationProcedure) {
            if ($availableCommunicationProcedure->value == $communicationProcedureName) {
                $deviceType->setCommunicationProcedure($availableCommunicationProcedure);
            }
        }

        $communicationProcedure->setDefaultFieldRequirements($deviceType);

        return $deviceType;
    }

    #[Rest\Get('/communication/procedure/requirements/{communicationProcedureName}')]
    #[Rest\View(serializerGroups: ['id', 'representation'])]
    #[Api\Summary('Get communication procedure requirements')]
    #[Api\Parameter(name: 'communicationProcedureName', in: 'path', description: 'Communication procedure name')]
    #[Api\Response200(
        description: 'Communication procedure requirements',
        content: new OA\JsonContent(
            example: '{
                "communicationProcedureRequirementsRequired": ["string"], 
                "communicationProcedureRequirementsOptional": ["string"],
                "communicationProcedureCertificateCategoryRequired": [
                    {
                    "id": 0,
                    "representation": "string"
                    }
                ],
                "communicationProcedureCertificateCategoryOptional": [
                    {
                    "id": 0,
                    "representation": "string"
                    }
                ],
                "deviceVpnCertificateType":  {
                    "id": 0,
                    "representation": "string"
                    }
            }'
        )
    )]
    #[Api\Response404('Communication procedure with specified name was not found')]
    public function communicationProcedureRequirements(string $communicationProcedureName)
    {
        $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByName($communicationProcedureName);

        if (!$communicationProcedure) {
            throw new NotFoundHttpException();
        }

        return [
            'communicationProcedureRequirementsRequired' => $communicationProcedure->getCommunicationProcedureRequirementsRequired(),
            'communicationProcedureRequirementsOptional' => $communicationProcedure->getCommunicationProcedureRequirementsOptional(),
            'communicationProcedureCertificateCategoryRequired' => $this->getCertificateTypesByCertificateCategories($communicationProcedure->getCommunicationProcedureCertificateCategoryRequired(), CertificateEntity::DEVICE),
            'communicationProcedureCertificateCategoryOptional' => $this->getCertificateTypesByCertificateCategories($communicationProcedure->getCommunicationProcedureCertificateCategoryOptional(), CertificateEntity::DEVICE),
            'deviceVpnCertificateType' => $this->getDeviceVpnCertificateType(),
        ];
    }
}
