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
use App\Deny\VpnOpenConnectionDenyInterface;
use App\Service\Helper\VpnManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

trait ApiVpnOpenConnectionTrait
{
    use VpnManagerTrait;

    /**
     * Description "vpn connection" is used instead of "VPN connection" because it is generated automatically on other endpoints. Keep it consistent.
     */
    #[Rest\Get('/{id}/open/vpnconnection', requirements: ['id' => '\d+'])]
    #[Api\Summary('Open vpn connection to {{ subjectLower }} by ID')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to open vpn connection')]
    #[Api\Response200SubjectGroups]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
    #[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
    public function vpnOpenConnectionAction(Request $request, int $id)
    {
        $object = $this->find($id, VpnOpenConnectionDenyInterface::VPN_OPEN_CONNECTION);

        $this->vpnManager->openConnection($this->getUser(), $object);

        $this->entityManager->persist($object);
        $this->entityManager->flush();

        $this->modifyResponseObject($object);

        return $object;
    }
}
