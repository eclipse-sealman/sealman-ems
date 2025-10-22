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
use App\Deny\VpnCloseConnectionDenyInterface;
use App\Entity\VpnConnection;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\VpnManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Exception\RequestExecutionException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ApiVpnCloseConnectionTrait
{
    use VpnManagerTrait;
    use SecurityHelperTrait;

    /**
     * Description "vpn connection" is used instead of "VPN connection" because it is generated automatically on other endpoints. Keep it consistent.
     */
    #[Rest\Get('/{id}/close/vpnconnection', requirements: ['id' => '\d+'])]
    #[Api\Summary('Close vpn connection by ID')]
    #[Api\ParameterPathId('ID of vpn connection to close')]
    #[Api\Response200Groups(description: 'Returns vpn connection', content: new NA\Model(type: VpnConnection::class))]
    #[Api\Response404Id('Vpn connection with specified ID was not found')]
    #[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
    #[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
    public function vpnCloseConnectionAction(Request $request, int $id)
    {
        // TODO Why this is used in a device/endpoint Controller not just to VpnConnectionController? What is the point?
        // find() cannot be used because this trait is also used in DeviceController and DeviceEndpointDeviceController - which makes entity class different
        if ($this->isAllDevicesGranted()) {
            // Admin can close connection not owned by him
            $object = $this->getRepository(VpnConnection::class)->findOneBy(['id' => $id]);
        } else {
            $object = $this->getRepository(VpnConnection::class)->findOneBy(['id' => $id, 'user' => $this->getUser()]);
        }

        if (!$object) {
            throw new NotFoundHttpException();
        }

        // Custom error handling to provide detailed 403 error - to match with clostAllConnections button
        if ($this->hasDenyClass()) {
            $denyClass = $this->getDenyClass();
            if ($this->isDenied($denyClass, VpnCloseConnectionDenyInterface::VPN_CLOSE_CONNECTION, $object)) {
                $this->fillDeny($denyClass, $object);
                $error = 'error.title.403';
                if (isset($object->getDeny()[VpnCloseConnectionDenyInterface::VPN_CLOSE_CONNECTION])) {
                    $error = $object->getDeny()[VpnCloseConnectionDenyInterface::VPN_CLOSE_CONNECTION];
                }
                throw new RequestExecutionException($error);
            }
        }

        $this->vpnManager->closeConnection($object);

        // This action is not using  $this->modifyResponseObject($object);, due to being used outside of VpnConnection controller ()
        return $object;
    }
}
