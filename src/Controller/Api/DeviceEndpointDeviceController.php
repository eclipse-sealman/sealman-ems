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
use App\Deny\DeviceEndpointDeviceDeny;
use App\Entity\DeviceEndpointDevice;
use App\Form\DeviceEndpointDeviceType;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\EventDispatcherTrait;
use App\Service\Helper\LockManagerTrait;
use App\Trait\ApiVpnCloseConnectionTrait;
use App\Trait\ApiVpnOpenConnectionTrait;
use Carve\ApiBundle\Attribute\AddRoleBasedSerializerGroups;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Controller\AbstractApiController;
use Carve\ApiBundle\Deny\AbstractApiObjectDeny;
use Carve\ApiBundle\Trait\ApiDeleteTrait;
use Carve\ApiBundle\Trait\ApiEditTrait;
use Carve\ApiBundle\Trait\ApiGetTrait;
use Carve\ApiBundle\Trait\ApiListTrait;
use Doctrine\ORM\QueryBuilder;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

#[Rest\Route('/deviceendpointdevice')]
#[Api\Resource(
    class: DeviceEndpointDevice::class,
    denyClass: DeviceEndpointDeviceDeny::class,
    editFormClass: DeviceEndpointDeviceType::class
)]
#[Rest\View(
    serializerGroups: [
        'identification',
        'deviceEndpointDevice:public',
        'deviceType:identification',
        'vpnConnection:deviceEndpointDevice',
        'timestampable',
        'blameable',
        'deny',
    ]
)]
#[AddRoleBasedSerializerGroups('ROLE_ADMIN_VPN', ['device:admin', 'device:adminVpn', 'deviceEndpointDevice:admin'])]
#[AddRoleBasedSerializerGroups('ROLE_VPN', ['device:vpn', 'deviceEndpointDevice:vpn'])]
#[AddRoleBasedSerializerGroups('ROLE_VPN_ENDPOINTDEVICES', ['deviceEndpointDevice:vpnEndpointDevices'])]
#[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
#[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
class DeviceEndpointDeviceController extends AbstractApiController
{
    use ApiDeleteTrait;
    use ApiGetTrait;
    use ApiEditTrait;
    use ApiListTrait;
    use ApiVpnCloseConnectionTrait;
    use ApiVpnOpenConnectionTrait;
    use EventDispatcherTrait;
    use SecurityHelperTrait;
    use LockManagerTrait;

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

        $object->setLock($this->lockManager->getEndpointDeviceLock($object));

        return $this->handleForm($this->getEditFormClass(), $request, [$this, 'processEdit'], $object, $this->getEditFormOptions());
    }

    /**
     * Method checks your credentials and removes:
     * - Not owned VPN connections from device and connected endpoint devices.
     * - Fill owned VPN connections from device and connected endpoint devices.
     */
    protected function modifyResponseObject(object $object): void
    {
        $this->removeNotOwnedAccessTags($object);

        if (!$this->isGranted('ROLE_ADMIN_VPN')) {
            $this->removeNotOwnedVpnConnections($object);
        }

        $this->fillOwnedVpnConnections($object);
    }

    protected function modifyQueryBuilder(QueryBuilder $queryBuilder, string $alias): void
    {
        $this->applyUserAccessTagsQueryModification($queryBuilder, $alias);
    }

    protected function processEdit($object, FormInterface $form)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->dispatchDeviceEndpointDeviceUpdated($object);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }

    protected function processDelete(object $object)
    {
        $this->dispatchDeviceEndpointDevicePreRemove($object);

        $this->entityManager->remove($object);
        $this->entityManager->flush();
    }
}
