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
use App\Deny\VpnConfigDenyInterface;
use App\Entity\User;
use App\Model\ConnectionStatus;
use App\Service\Helper\CertificateManagerTrait;
use App\Service\Helper\VpnManagerTrait;
use App\Service\Trait\CertificateTypeHelperTrait;
use Carve\ApiBundle\Attribute as Api;
use Carve\ApiBundle\Service\Helper\DenyManagerTrait;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation as NA;
use OpenApi\Attributes as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Rest\Route('/vpn')]
#[Security("is_granted('ROLE_ADMIN_VPN') or is_granted('ROLE_VPN')")]
#[Areas(['admin:vpnsecuritysuite', 'vpnsecuritysuite'])]
#[OA\Tag('Vpn')]
class VpnController extends AbstractFOSRestController
{
    use DenyManagerTrait;
    use CertificateManagerTrait;
    use CertificateTypeHelperTrait;
    use VpnManagerTrait;

    #[Rest\Get('/connection/status')]
    #[Rest\View(serializerGroups: ['identification', 'user:openVpnPublic', 'vpnConnection:public'])]
    #[Api\Summary('Get connection status')]
    #[Api\Response200Groups(description: 'Returns connection status', content: new NA\Model(type: ConnectionStatus::class))]
    public function statusAction()
    {
        return $this->vpnManager->getUserConnectionStatus();
    }

    #[Rest\Get('/certificates')]
    #[Rest\View(serializerGroups: ['identification', 'certificate:private', 'deny'])]
    #[Api\Summary('Get user certificate information')]
    #[Api\Response200Groups(description: 'Returns user certificate information', content: new NA\Model(type: User::class))]
    public function certificatesAction()
    {
        $object = $this->getUser();

        $this->fillDeny(UserDeny::class, $object);

        foreach ($object->getUseableCertificates() as $useableCertificate) {
            if ($useableCertificate->getCertificateType() == $this->getTechnicianVpnCertificateType()) {
                $useableCertificate->getCertificate()->setDecryptedCertificateCa($this->certificateManager->getCertificateCa($useableCertificate->getCertificate()));
                $useableCertificate->getCertificate()->setDecryptedCertificate($this->certificateManager->getCertificate($useableCertificate->getCertificate()));
                $useableCertificate->getCertificate()->setDecryptedPrivateKey($this->certificateManager->getPrivateKey($useableCertificate->getCertificate()));
            }
        }

        return $object;
    }

    // TODO Why this is named /client/config? What client referes to?
    // /vpn/client/config - OpenVPN client configuration - not OpenVPN server configuration - is it ok?
    // please remove if explanation is valid
    #[Rest\Get('/client/config')]
    #[Api\Summary('Download user OpenVPN configuration')]
    #[Api\Response200(description: 'OpenVPN configuration', content: new OA\MediaType(mediaType: 'application/x-openvpn-profile', schema: new OA\Schema(type: 'string')))]
    public function clientConfigAction()
    {
        $user = $this->getUser();

        if ($this->isDenied(UserDeny::class, VpnConfigDenyInterface::DOWNLOAD_VPN_CONFIG, $user)) {
            throw new AccessDeniedHttpException();
        }

        $filename = $this->vpnManager->getOpenVpnConfigurationFilename($user);
        $content = $this->vpnManager->generateConfiguration($user);

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
