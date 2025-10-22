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
use App\Deny\DeviceDeny;
use App\Deny\DeviceSecretDeny;
use App\Entity\Device;
use App\Entity\DeviceSecret;
use App\Entity\DeviceTypeSecret;
use App\Form\DeviceSecretType;
use App\Model\VariableValueModel;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\DeviceSecretManagerTrait;
use App\Service\Helper\EntityManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;
use Carve\ApiBundle\Model\ListQuerySortingInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Rest\Route('/devicesecret')]
#[Api\Resource(
    class: DeviceSecret::class,
    createFormClass: DeviceSecretType::class,
    editFormClass: DeviceSecretType::class,
    denyClass: DeviceSecretDeny::class
)]
#[Rest\View(serializerGroups: ['identification', 'deviceSecret:public', 'timestampable', 'blameable', 'deny'])]
#[Security("is_granted('ROLE_ADMIN') or is_granted('ROLE_SMARTEMS') or is_granted('ROLE_VPN')")]
#[Areas(['admin', 'smartems', 'vpnsecuritysuite'])]
class DeviceSecretController extends AbstractApiController
{
    use EntityManagerTrait;
    use SecurityHelperTrait;
    use DeviceCommunicationFactoryTrait;
    use DeviceSecretManagerTrait;

    #[Rest\Get('/{id}/generate/device/secret', requirements: ['id' => '\d+'])]
    #[Api\Summary('Generate random secret value for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to use')]
    #[Api\Response200(description: 'Generated random secret value')]
    #[Api\Response404Id]
    public function generateDeviceSecretAction(Request $request, int $id)
    {
        $deviceSecret = $this->find($id, DeviceSecretDeny::EDIT);

        return $this->deviceSecretManager->generateRandomSecret($deviceSecret);
    }

    #[Rest\Get('/{deviceId}/{deviceTypeSecretId}/generate/device/type/secret', requirements: ['id' => '\d+'])]
    #[Api\Summary('Generate random secret value for {{ subjectLower }} by ID')]
    #[Api\Parameter(name: 'deviceId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device')]
    #[Api\Parameter(name: 'deviceTypeSecretId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device type secret')]
    #[Api\Response200(description: 'Generated random secret value')]
    #[Api\Response404('Device or DeviceTypeSecret with specified ID was not found')]
    public function generateDeviceTypeSecretAction(Request $request, int $deviceId, int $deviceTypeSecretId)
    {
        $deviceSecret = $this->getDeviceSecretByDeviceIdDeviceTypeSecretId($deviceId, $deviceTypeSecretId, DeviceSecretDeny::CREATE);

        return $this->deviceSecretManager->generateRandomSecret($deviceSecret);
    }

    #[Rest\Get('/{id}/show', requirements: ['id' => '\d+'])]
    #[Api\Summary('Show {{ subjectLower }} value by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to return')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Rest\View(serializerGroups: ['identification', 'deviceSecret:secret', 'variable:valueModel'])]
    public function showAction(Request $request, int $id)
    {
        $object = $this->find($id, DeviceSecretDeny::SHOW);

        $this->modifyResponseObject($object);

        $this->deviceSecretManager->createUserShowSecretLog($object);

        $this->entityManager->flush();

        // Decryption
        $object->setDecryptedSecretValue($this->deviceSecretManager->getDecryptedSecretValue($object));

        // Generating encoded device secret variables array
        return $this->fillInEncodedVariables($object);
    }

    #[Rest\Get('/{deviceSecretId}/show/variables/{deviceId}', requirements: ['deviceSecretId' => '\d+', 'deviceId' => '\d+'])]
    #[Api\Summary('Show {{ subjectLower }} variables by device secret ID and device ID')]
    #[Api\Parameter(in: 'path', name: 'deviceSecretId', description: 'Device secret ID')]
    #[Api\Parameter(in: 'path', name: 'deviceId', description: 'Device ID')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Rest\View(serializerGroups: ['identification', 'deviceSecret:secret', 'variable:valueModel'])]
    public function showVariablesAction(Request $request, int $deviceSecretId, int $deviceId)
    {
        $object = new DeviceSecret();
        $object->setDevice($this->getRepository(Device::class)->find($deviceId));
        $object->setDeviceTypeSecret($this->getRepository(DeviceTypeSecret::class)->find($deviceSecretId));

        $denyKey = DeviceSecretDeny::SHOW_VARIABLES;
        $denyClass = $this->getDenyClass();
        if ($this->isDenied($denyClass, $denyKey, $object)) {
            throw new AccessDeniedHttpException();
        }

        $this->fillDeny($denyClass, $object);

        $this->modifyResponseObject($object);

        $object->setDecryptedSecretValue('example');

        // Generating encoded device secret variables array
        return $this->fillInEncodedVariables($object);
    }

    protected function fillInEncodedVariables(DeviceSecret $object)
    {
        // Generating encoded device secret variables array
        if ($object->getDeviceTypeSecret()->getUseAsVariable()) {
            $communicationProcedure = $this->deviceCommunicationFactory->getDeviceCommunicationByDevice($object->getDevice());
            if ($communicationProcedure) {
                $variablesArray = $communicationProcedure->getDeviceSecretValueEncodedVariables($object, $object->getDecryptedSecretValue());
                $encodedVariablesCollection = new ArrayCollection();

                foreach ($variablesArray as $key => $value) {
                    $encodedVariablesCollection->add(new VariableValueModel($key, $value));
                }

                $object->setEncodedVariables($encodedVariablesCollection);
            }
        }

        return $object;
    }

    #[Rest\Get('/{deviceId}/{deviceTypeSecretId}/info')]
    #[Api\Parameter(name: 'deviceId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device')]
    #[Api\Summary('Get {{ subjectLower }} information')]
    #[Api\Response200SubjectGroups('Returns {{ subjectLower }} information')]
    #[Api\Response404('Device or DeviceTypeSecret with specified ID was not found')]
    #[Api\Response400]
    public function infoAction(Request $request, int $deviceId, int $deviceTypeSecretId)
    {
        return $this->getDeviceSecretByDeviceIdDeviceTypeSecretId($deviceId, $deviceTypeSecretId, DeviceSecretDeny::CREATE);
    }

    #[Rest\Post('/{deviceId}/{deviceTypeSecretId}/create')]
    #[Api\Summary('Create {{ subjectLower }}')]
    #[Api\Parameter(name: 'deviceTypeSecretId', in: 'path', schema: new OA\Schema(type: 'integer'), description: 'ID of device type secret')]
    #[Api\RequestBodyCreate]
    #[Api\Response200SubjectGroups('Returns created {{ subjectLower }}')]
    #[Api\Response404('Device or DeviceTypeSecret with specified ID was not found')]
    #[Api\Response400]
    public function createAction(Request $request, int $deviceId, int $deviceTypeSecretId)
    {
        $object = $this->getDeviceSecretByDeviceIdDeviceTypeSecretId($deviceId, $deviceTypeSecretId, DeviceSecretDeny::CREATE);

        return $this->handleForm($this->getCreateFormClass(), $request, function ($object, FormInterface $form) {
            $this->deviceSecretManager->encryptDeviceSecret($object);
            $object->setRenewedAt(new \DateTime());
            $this->entityManager->persist($object);

            $this->deviceSecretManager->createUserCreateSecretLog($object);

            $this->entityManager->flush();

            $this->modifyResponseObject($object);

            return $object;
        }, $object);
    }

    #[Rest\Get('/{id}/enable/force/renewal', requirements: ['id' => '\d+'])]
    #[Api\Summary('Enable force renewal flag for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }}')]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response404Id]
    public function enableForceRenewalAction(int $id)
    {
        $object = $this->find($id, DeviceSecretDeny::ENABLE_FORCE_RENEWAL);

        $object->setForceRenewal(true);

        $this->entityManager->persist($object);

        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    #[Rest\Get('/{id}/disable/force/renewal', requirements: ['id' => '\d+'])]
    #[Api\Summary('Disable force renewal flag for {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }}')]
    #[Api\Response200SubjectGroups('Returns edited {{ subjectLower }}')]
    #[Api\Response404Id]
    public function disableForceRenewalAction(int $id)
    {
        $object = $this->find($id, DeviceSecretDeny::DISABLE_FORCE_RENEWAL);

        $object->setForceRenewal(false);

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
        $object = $this->find($id, DeviceSecretDeny::EDIT);

        $objectPrevious = clone $object;

        return $this->handleForm($this->getEditFormClass(), $request, function ($object, FormInterface $form) use ($objectPrevious) {
            $this->deviceSecretManager->encryptDeviceSecret($object);
            $object->setRenewedAt(new \DateTime());

            $this->entityManager->persist($object);

            $this->deviceSecretManager->createUserEditSecretLog($object, $objectPrevious->getSecretValue());

            $this->entityManager->flush();

            $this->modifyResponseObject($object);

            return $object;
        }, $object);
    }

    #[Rest\Delete('/{id}', requirements: ['id' => '\d+'])]
    #[Api\Summary('Clear {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to clear')]
    #[Api\Response204('{{ subjectTitle }} successfully cleared')]
    #[Api\Response404Id]
    public function clearAction(int $id)
    {
        $object = $this->find($id, DeviceSecretDeny::CLEAR);

        $this->deviceSecretManager->createUserClearSecretLog($object);

        $this->entityManager->remove($object);
        $this->entityManager->flush();

        return;
    }

    #[Rest\Post('/{id}/list', requirements: ['id' => '\d+'])]
    #[Api\Summary('List {{ subjectPluralLower }} by device ID')]
    #[Api\ParameterPathId('ID of device to get device secrets from')]
    #[Api\RequestBodyList]
    #[Api\Response200List('Returns list of {{ subjectPluralLower }}')]
    #[Api\Response400]
    #[Api\Response404Id]
    public function listAction(Request $request, int $id)
    {
        // Make sure that query is consistent with queries in DeviceController
        $alias = 'd';
        $queryBuilder = $this->getRepository(Device::class)->createQueryBuilder($alias);

        $this->applyUserAccessTagsQueryModification($queryBuilder, $alias);

        $queryBuilder->andWhere($alias.'.id = :id');
        $queryBuilder->setParameter('id', $id);
        $queryBuilder->setMaxResults(1);

        $device = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!$device) {
            throw new NotFoundHttpException();
        }

        if ($this->isDenied(DeviceDeny::class, AbstractApiObjectDeny::GET, $device)) {
            throw new AccessDeniedHttpException();
        }

        // Code below assumes that User has access to the $device
        return $this->handleForm($this->getListFormClass(), $request, function ($object, FormInterface $form) use ($device) {
            return $this->processDeviceSecretsList($object, $form, $device);
        }, null, $this->getDefaultListFormOptions());
    }

    // Custom sorting for columns from DeviceSecret entity
    protected function modifySorting(ListQuerySortingInterface $sorting, QueryBuilder $queryBuilder, string $alias): bool
    {
        $deviceSecretFields = ['renewedAt', 'updatedAt', 'updatedBy', 'createdAt', 'createdBy'];

        if (in_array($sorting->getField(), $deviceSecretFields)) {
            $queryBuilder->addOrderBy('ds.'.$sorting->getField(), $sorting->getDirection()->value);

            return true;
        }

        return false;
    }

    // Due to custom requirements, custom sorting and filtering choices needs to be prepared
    protected function getDefaultListFormOptions(): array
    {
        $choices = ['name', 'description', 'renewedAt', 'updatedAt', 'updatedBy', 'createdAt', 'createdBy'];

        return [
            'sorting_field_choices' => $choices,
            'filter_filterBy_choices' => $choices,
        ];
    }

    protected function processDeviceSecretsList($listQuery, FormInterface $form, Device $device)
    {
        /*
        Due to custom requirement - DeviceSecret can be not created as record,
        but it still should be returned in list if corresponding DeviceTypeSecret exists
        List starts with DeviceTypeSecret query (which has all available results), and then is repacking data into DeviceSecret list
        */

        // Make sure that query is consistent with queries in DeviceController
        // Customized getListQueryBuilder
        $alias = 'o';
        $queryBuilder = $this->getRepository(DeviceTypeSecret::class)->createQueryBuilder($alias);
        $queryBuilder->select([$alias, 'ds']);
        $queryBuilder->distinct();

        $queryBuilder->andWhere($alias.'.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $device->getDeviceType());

        if (!$this->isGranted('ROLE_ADMIN')) {
            $queryBuilder->andWhere(':accessTags MEMBER OF '.$alias.'.accessTags');
            $queryBuilder->setParameter('accessTags', $this->getUser()->getAccessTags());
        }

        // This part of query before joining uses only device secrets that belong to the device
        $queryBuilder->leftJoin($alias.'.deviceSecrets', 'ds', 'WITH', 'ds.device = :device ');
        $queryBuilder->setParameter('device', $device);

        $this->applyListQuerySorting($listQuery->getSorting(), $queryBuilder, $alias);
        $this->applyListQueryFilters($listQuery->getFilters(), $queryBuilder, $alias);

        $page = $listQuery->getPage() ?? 1;
        $rowsPerPage = $listQuery->getRowsPerPage() ?? 10;
        $queryBuilder->setFirstResult(($page - 1) * $rowsPerPage);
        $queryBuilder->setMaxResults($rowsPerPage);
        $deviceTypeSecrets = $queryBuilder->getQuery()->getResult();

        // Row count query
        $rowCountQueryBuilder = clone $queryBuilder;
        $rowCountQueryBuilder->select('COUNT(DISTINCT o.id)');
        $rowCountQueryBuilder->resetDQLPart('orderBy');
        $rowCountQueryBuilder->setFirstResult(0);
        $rowCountQueryBuilder->setMaxResults(1);
        $rowCount = $rowCountQueryBuilder->getQuery()->getSingleScalarResult();

        // Repacking results
        $results = [];
        foreach ($deviceTypeSecrets as $deviceTypeSecret) {
            $deviceSecret = $deviceTypeSecret->getDeviceSecrets()->first();
            if (false === $deviceSecret) {
                $deviceSecret = new DeviceSecret();
                $deviceSecret->setDevice($device);
                $deviceSecret->setDeviceTypeSecret($deviceTypeSecret);
            }
            $results[] = $deviceSecret;
        }

        if ($this->hasDenyClass()) {
            $denyClass = $this->getDenyClass();
            foreach ($results as $result) {
                $this->fillDeny($denyClass, $result);
                $this->modifyResponseObject($result);
            }
        }

        return [
            'results' => $results,
            'rowCount' => (int) $rowCount,
        ];
    }

    // Get DeviceSecret from deviceTypeSecretId and DeviceId
    protected function getDeviceSecretByDeviceIdDeviceTypeSecretId(int $deviceId, int $deviceTypeSecretId, string $denyKey): DeviceSecret
    {
        // Validations will be handled by DeviceSecretDeny
        $device = $this->getRepository(Device::class)->find($deviceId);
        if (!$device) {
            throw new NotFoundHttpException();
        }

        $deviceTypeSecret = $this->getRepository(DeviceTypeSecret::class)->find($deviceTypeSecretId);
        if (!$deviceTypeSecret) {
            throw new NotFoundHttpException();
        }

        $object = new DeviceSecret();
        $object->setDevice($device);
        $object->setDeviceTypeSecret($deviceTypeSecret);

        if ($this->isDenied(DeviceSecretDeny::class, $denyKey, $object)) {
            throw new AccessDeniedHttpException();
        }

        return $object;
    }
}
