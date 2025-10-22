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
use App\Deny\VpnConfigDenyInterface;
use App\Service\Helper\VpnManagerTrait;
use Carve\ApiBundle\Attribute as Api;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait ApiVpnDownloadConfigurationTrait
{
    use VpnManagerTrait;

    #[Rest\Get('/{id}/download/vpn/config', requirements: ['id' => '\d+'])]
    #[Api\Summary('Download {{ subjectLower }} OpenVPN configuration')]
    #[Api\ParameterPathId('ID of {{ subjectLower }} to download OpenVPN configuration')]
    #[Api\Response200(description: 'OpenVPN configuration', content: new OA\MediaType(mediaType: 'application/x-openvpn-profile', schema: new OA\Schema(type: 'string')))]
    #[Api\Response404Id]
    #[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
    #[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
    public function downloadVpnConfigurationAction(Request $request, int $id)
    {
        $object = $this->find($id, VpnConfigDenyInterface::DOWNLOAD_VPN_CONFIG);

        $filename = $this->vpnManager->getOpenVpnConfigurationFilename($object);
        $content = $this->vpnManager->generateConfiguration($object);

        $response = new Response($content);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $filename
        );
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', 'application/x-openvpn-profile');

        return $response;
    }
}
