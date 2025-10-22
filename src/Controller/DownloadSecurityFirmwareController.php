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

namespace App\Controller;

use App\Entity\Firmware;
use App\Security\SecurityHelperTrait;
use App\Service\Helper\DeviceCommunicationFactoryTrait;
use App\Service\Helper\EntityManagerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//This controllers handles firmware download security while user is using webUI download button
class DownloadSecurityFirmwareController extends AbstractController
{
    use EntityManagerTrait;
    use DeviceCommunicationFactoryTrait;
    use SecurityHelperTrait;

    public function checkAuthAction(Request $request)
    {
        $uri = $request->headers->get('X-Original-URI');
        if (!$uri) {
            return $this->unauthorized();
        }

        $parsedUri = $this->parseUri($uri);
        if (null === $parsedUri) {
            return $this->unauthorized();
        }

        [$deviceTypeSlug, $uuid, $filename] = $parsedUri;

        $deviceType = $this->deviceCommunicationFactory->getDeviceTypeBySlug($deviceTypeSlug);
        if (!$deviceType) {
            return $this->unauthorized();
        }

        $queryBuilder = $this->getRepository(Firmware::class)->createQueryBuilder('f');
        $queryBuilder->andWhere('f.uuid = :uuid');
        $queryBuilder->setParameter('uuid', $uuid);
        $queryBuilder->andWhere('f.filename = :filename');
        $queryBuilder->setParameter('filename', $filename);
        $queryBuilder->andWhere('f.deviceType = :deviceType');
        $queryBuilder->setParameter('deviceType', $deviceType);
        $queryBuilder->setMaxResults(1);

        // Add user security for query - same as for showing firmware in webUI list
        $this->applyUserAccessTagsQueryModificationForTemplateComponents($queryBuilder, 'f');

        $firmware = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!$firmware) {
            return $this->unauthorized();
        }

        // Using legacy uuid as folder name if exists (meaning firmware was created before v3.3.0)
        $folderName = $firmware->getLegacyUuid() ? $firmware->getLegacyUuid() : $firmware->getUuid();
        $firmwareFilepath = $deviceTypeSlug.'/'.$folderName.'/'.$firmware->getFilename();

        return new Response(null, Response::HTTP_NO_CONTENT, ['FIRMWARE-FILEPATH' => $firmwareFilepath]);
    }

    /**
     * Validate and parse $uri. When a valid $uri is passed function will return
     * an array with $deviceTypeSlug, $uuid, $filename. Otherwise it returns null.
     */
    protected function parseUri(string $uri): ?array
    {
        // $uri is expected to be structured in following way:
        // /web/api/download/firmware/DEVICE_TYPE_SLUG/UUID/FILENAME

        $prefix = '/web/api/download/firmware/';

        if (!str_starts_with($uri, $prefix)) {
            return null;
        }

        $uriParts = explode('/', substr($uri, \strlen($prefix)));

        if (3 != count($uriParts)) {
            return null;
        }

        if (!$uriParts[0] || !$uriParts[1] || !$uriParts[2]) {
            return null;
        }

        return [$uriParts[0], $uriParts[1], $uriParts[2]];
    }

    protected function unauthorized(): Response
    {
        return new Response(null, Response::HTTP_UNAUTHORIZED);
    }
}
